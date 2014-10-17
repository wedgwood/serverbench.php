<?php
/**
 * serverbench server app
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\App\Server;

use ServerBench\App\Util\Config;
use ServerBench\Core\Daemon;
use ServerBench\Logger\ConsoleLogger;
use ServerBench\Logger\Logger;
use ServerBench\Logger\SysLogger;

class App
{
    public function check()
    {
        $ret = true;

        foreach (array('zmq', 'pcntl', 'posix') as $ext) {
            if (extension_loaded($ext)) {
                ConsoleLogger::success(sprintf(
                    'extension(%s) loaded',
                    $ext
                ));
            } else {
                ConsoleLogger::failed(sprintf(
                    'extension(%s) not loaded',
                    $ext
                ));

                $ret = false;
            }
        }

        return $ret;
    }

    public function bootstrap($conf_dir = NULL, $conf = array())
    {
        ConsoleLogger::info("\n\n====== BOOTSTRAP START ======\n");

        define('APP_DIR', dirname(realpath($_SERVER['SCRIPT_FILENAME'])) . '/');
        define('APP_PID_PATH', APP_DIR . '/.pid');

        if (file_exists(APP_PID_PATH)) {
            ConsoleLogger::failed('[!]one instance of the app is running');
            return false;
        }

        if (!$this->check()) {
            ConsoleLogger::failed("\n\n====== BOOTSTRAP FAILED ======\n");
            return false;
        }

        ConsoleLogger::success('APP_DIR  ' . APP_DIR);

        if (!$conf_dir) {
            $conf_dir = APP_DIR . 'conf';
        }

        ConsoleLogger::success('CONF_DIR ' . $conf_dir);
        ConsoleLogger::success('... import serverbench config');

        if (file_exists($conf_dir . '/serverbench.ini')) {
            Config::importIniFile($conf_dir . '/serverbench.ini');
        }

        ConsoleLogger::success('... import app config');

        if (file_exists($conf_dir . '/app.ini')) {
            Config::importIniFile($conf_dir . '/app.ini');
        }


        Config::importArray($conf);
        return $this->run();
    }

    public function run()
    {
        $sys_log_dir = str_replace(
            '{APP_DIR}',
            APP_DIR,
            Config::get('system.log_dir', '{APP_DIR}/log')
        );

        // setup sys logger
        $sys_rfh = new \Monolog\Handler\RotatingFileHandler(
            $sys_log_dir . 'serverbench',
            0,
            Config::get('app.log_level', 'debug'),
            false
        );

        $sys_rfh->setFilenameFormat('{filename}.{date}.log', 'Ymd');

        $sys_logger = new \Monolog\Logger(
            'serverbench',
            array($sys_rfh),
            array(new \Monolog\Processor\ProcessIdProcessor())
        );

        SysLogger::setLogger($sys_logger);

        // setup app logger
        $app_name = Config::get('app.name', 'app');

        $app_log_dir = str_replace(
            '{APP_DIR}',
            APP_DIR,
            Config::get('app.log_dir', '{APP_DIR}/log')
        );

        $app_rfh = new \Monolog\Handler\RotatingFileHandler(
            $app_log_dir . $app_name,
            0,
            Config::get('app.log_level', 'debug'),
            false
        );

        $app_rfh->setFilenameFormat('{filename}.{date}.log', 'Ymd');

        $app_logger = new \Monolog\Logger(
            $app_name,
            array($app_rfh),
            array(new \Monolog\Processor\ProcessIdProcessor())
        );

        Logger::setLogger($app_logger);

        ConsoleLogger::success('... import apis');

        $c = new \ServerBench\Controller\Controller();

        // no use now
        $proxy_api = new \ServerBench\Api\Api();
        $worker_api = new \ServerBench\Api\Api();

        $api_class  = Config::get('app.api');

        if (!class_exists($api_class)) {
            $path = str_replace('{APP_DIR}', APP_DIR, $api_class);

            do {
                if (file_exists($path)) {
                    $pi = pathinfo($path);
                    $api_class = $pi['filename'];
                    require $path;

                    if (class_exists($api_class)) {
                        break;
                    }
                }

                ConsoleLogger::failed('failed get api for worker');
                ConsoleLogger::failed("\n\n====== BOOTSTRAP FAILED ======\n");
                return false;
            } while (0);
        }

        $rc = $worker_api->import($api_class);
        ConsoleLogger::success('app.api  ' . $api_class);

        if (false === $rc || $worker_api->isEmpty()) {
            ConsoleLogger::failed('failed to import api for worker');
            ConsoleLogger::failed("\n\n====== BOOTSTRAP FAILED ======\n");
            return false;
        }

        ConsoleLogger::info("\n====== BOOTSTRAP END   ======\n");

        file_put_contents(APP_PID_PATH, getmypid());

        $c->run(array(
            'controller'             => array(
                'name'               => $app_name . '[controller]',
                'acceptor'           => Config::get('controller.acceptor')
            ),
            'proxy'                  => array(
                'name'               => $app_name . '[proxy]',
                'acceptor'           => Config::get('app.bind'),
                'connector'          => Config::get('proxy.connector'),
                'api'                => $proxy_api
            ),
            'worker'                 => array(
                'name'               => $app_name . '[worker]',
                'num'                => Config::get('app.workers', 1),
                'acceptor'           => Config::get('worker.acceptor'),
                'wait_ms'            => Config::get('worker.wait_ms', 30000),
                'heartbeat_interval' => Config::get('worker.heartbeat_interval', 60),
                'coder'              => Config::get('app.coder', NULL),
                'api'                => $worker_api
            )
        ));

        @unlink(APP_PID_PATH);
    }
}
