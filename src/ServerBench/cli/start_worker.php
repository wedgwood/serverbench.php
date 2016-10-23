<?php
/**
 * cli entry of serverbench's worker's pool
 *
 * @author Yuan B.J.
 */

require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require __DIR__ . '/misc.php';

use ServerBench\App\Server\Api;
use ServerBench\Base\CliArguments;
use ServerBench\Process\Util as ProcessUtil;
use ServerBench\App\Server\PeriodicGc;
use ServerBench\Worker\Worker;

if (PHP_SAPI !== 'cli') {
    \ServerBench\Cli\printf_and_exit("please run @ cli.\n");
}

$arguments = new CliArguments([
    'd'  => 'daemonize',
    'D:' => 'dir:',
    'T:' => 'title:',
    'app:',
    'ipcs:'
]);

$dir = $arguments->get('dir', getcwd());

if (isset($dir)) {
    chdir($dir);
}

$app_path = $arguments->get('app');

if (!isset($app_path)) {
    \ServerBench\Cli\printf_and_exit("arugment --app should be set.\n");
}

if (!file_exists($app_path)) {
    \ServerBench\Cli\printf_and_exit("app(%s) does not exist.", $app_path);
}

if (!isset($arguments['ipcs'])) {
    \ServerBench\Cli\printf_and_exit("argument --ipcs should be set.\n");
}

$ipcs = explode(',', $arguments['ipcs']);

if (count($ipcs) != 2) {
    \ServerBench\Cli\printf_and_exit("argument --ipcs should be a couple of sock files.\n");
}

ProcessUtil::setTitle($arguments->get('title', 'serverbench<worker>'));

try {
    $api = new Api('worker', include($app_path));

    if (false === $api->init()) {
        \ServerBench\syslog_error('app->init(\'worker\') returns false.');
        exit();
    }

    PeriodicGc::enable(300);

    $worker = new Worker($ipcs, function ($message) use ($api) {
        return $api->process($message);
    });

    $worker->run();

    if (false === $api->fini()) {
        \ServerBench\syslog_error('app->fini(\'worker\') returns false.');
    }
} catch (Exception $e) {
    \ServerBench\syslog_error('uncaught exception from worker: %s', [$e]);
}
