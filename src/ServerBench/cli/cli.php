<?php
/**
 * cli entry of serverbench's app
 *
 * @author Yuan B.J.
 */

require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require __DIR__ . '/misc.php';

use ServerBench\App\Server\Api;
use ServerBench\Base\CliArguments;
use ServerBench\Controller\Controller;
use ServerBench\Process\Util as ProcessUtil;
use ServerBench\App\Server\PeriodicGc;

if (PHP_SAPI !== 'cli') {
    \ServerBench\Cli\printf_and_exit("please run @ cli.\n");
    exit();
}

printf("\n%s\n", \ServerBench\Cli\get_logo());

$arguments = new CliArguments([
    'c:'  => 'config:',
    'l:'  => 'listen:',
    'd'   => 'daemonize',
    'D:'  => 'dir:',
    'h'   => 'help',
    'n::' => 'worker::',
    'v'   => 'version',
    'T:'  => 'title:',
    'reload',
    'stop',
    'status',
    'pidfile:',
    'pid:',
    'codec:',
    'app:',
    'ipcs:'
]);

$not_met = [];

if (!\ServerBench\Cli\check_requirements($not_met)) {
    \ServerBench\syslog_error('requirements is not met.');

    foreach ($not_met as $item) {
        \ServerBench\syslog_error('failed to load extension(%s)', [$item]);
    }

    exit();
}

if (isset($arguments['version'])) {
    \ServerBench\Cli\printf_and_exit("version %s\n", \ServerBench\Cli\get_version());
}

if (isset($arguments['help'])) {
    \ServerBench\Cli\printf_and_exit("%s\n", \ServerBench\Cli\get_usage());
}

$phpbin = $_SERVER['_'];
$script = $_SERVER['PWD'] . '/' . $_SERVER['PHP_SELF'];

if (isset($arguments['config'])) {
    $conf_path = realpath($arguments['config']);

    if (!file_exists($conf_path)) {
        \ServerBench\Cli\printf_and_exit("conf(%s) does not exist.\n", $arguments['config']);
    }

    $conf = parse_ini_file($conf_path, true);

    foreach ($conf as $k => $v) {
        $arguments[$k] = $v;
    }
}

$title = $arguments->get('title', 'serverbench');
$dir = $arguments->get('dir', getcwd());

if (isset($dir)) {
    $dir = realpath($dir);
    \ServerBench\syslog_info('cd %s', [$dir]);
    chdir($dir);
}

$get_pidarg = function () use ($arguments) {
    $pid = $arguments->get('pid');

    if (!isset($pid)) {
        $pidfile = $arguments->get('pidfile', './pid');

        if (isset($pidfile)) {
            $pidfile = realpath($pidfile);

            if (file_exists($pidfile)) {
                $pid = file_get_contents($pidfile);
            }
        }
    }

    if (isset($pid)) {
        return $pid;
    }

    return null;
};

if (isset($arguments['reload'])) {
    $pid = $get_pidarg();

    if (!isset($pid)) {
        printf_and_exit("no pid found.\n");
    }

    \ServerBench\Cli\reload_server($pid);
    exit();
}

if (isset($arguments['stop'])) {
    $pid = $get_pidarg();

    if (!isset($pid)) {
        \ServerBench\Cli\printf_and_exit("no pid found.\n");
    }

    \ServerBench\Cli\stop_server($pid);
    exit();
}

if (isset($arguments['status'])) {
    $pid = $get_pidarg();

    if (!isset($pid)) {
        \ServerBench\Cli\printf_and_exit("no pid found.\n");
    }

    \ServerBench\Cli\show_status($pid);
    exit();
}

$listen_addr = $arguments->get('listen');

if (!isset($listen_addr)) {
    \ServerBench\Cli\printf_and_exit("argument --listen or -l should be set.\n");
}

\ServerBench\syslog_info('listen %s', [$listen_addr]);

$app_path = $arguments->get('app');

if (!isset($app_path)) {
    \ServerBench\Cli\printf_and_exit("arugment --app should be set.\n");
}

$app_path = realpath($app_path);

if (!file_exists($app_path)) {
    \ServerBench\Cli\printf_and_exit("app(%s) does not exist.\n", $app_path);
}

$ipcs = $arguments->get('ipcs');

if (!isset($ipcs)) {
    $rand = mt_rand();
    $ipcs = sprintf('ipc://%s/ipc%d_%s_0.sock,ipc://%s/ipc%d_%s_1.sock', $dir, $rand, $title, $dir, $rand, $title);
}

\ServerBench\syslog_info('app %s', [$app_path]);

$workers = $arguments->get('workers', 1);
\ServerBench\syslog_info('start workers %d', [$workers]);

if (isset($arguments['daemonize'])) {
    \ServerBench\syslog_info('run as daemon.');
    ProcessUtil::daemon();
}

$pid = getmypid();
\ServerBench\syslog_info('pid of controller is %d', [$pid]);

$pidfile = $arguments->get('pidfile');

if (isset($pidfile)) {
    $realpath_pidfile = realpath($pidfile);

    if (file_exists($realpath_pidfile) && file_get_contents($pidfile) != '') {
        \ServerBench\syslog_error('there is a running instance already.');
        exit();
    }

    file_put_contents($pidfile, $pid);

    register_shutdown_function(function () use ($pidfile) {
        @unlink($pidfile);
    });
}

ProcessUtil::setTitle($arguments->get('title', 'serverbench<controller>'));

try {
    $api = new Api('controller', include($app_path));

    if (false === $api->init()) {
        \ServerBench\syslog_error('app->init(\'controller\') returns false.');
        exit();
    }

    PeriodicGc::enable(300);

    $controller = new Controller();
    $controller->run(
        [
            'groups' => [
                [
                    'proxy' => [
                        'routine' => [
                            $phpbin,
                            [
                                '-r', sprintf('require(\'%s\');', __DIR__ . '/start_proxy.php'),
                                '--',
                                // __DIR__ . '/start_proxy.php',
                                '--title', sprintf('%s<proxy>', $title),
                                '--listen', $listen_addr,
                                '--app', $app_path,
                                '--ipcs', $ipcs
                            ]
                        ]
                    ],
                    'worker' => [
                        'routine' => [
                            $phpbin,
                            [
                                '-r', sprintf('require(\'%s\');', __DIR__ . '/start_worker.php'),
                                '--',
                                // __DIR__ . '/start_worker.php',
                                '--title', sprintf('%s<worker>', $title),
                                '--app', $app_path,
                                '--ipcs', $ipcs
                            ]
                        ],
                        'num' => $workers
                    ]
                ]
            ]
        ],
        $dir
    );

    if (false === $api->fini()) {
        \ServerBench\syslog_error('app->fini(\'controller\') returns false.');
    }
} catch (Exception $e) {
    \ServerBench\syslog_error('uncaught exception from controller: %s', [$e]);
}
