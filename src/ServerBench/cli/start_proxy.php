<?php
/**
 * cli entry of serverbench's proxies
 *
 * @author Yuan B.J.
 */

require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require __DIR__ . '/misc.php';

use ServerBench\App\Server\Api;
use ServerBench\Base\CliArguments;
use ServerBench\Process\Util as ProcessUtil;
use ServerBench\App\Server\PeriodicGc;
use ServerBench\Proxy\Proxy;

if (PHP_SAPI !== 'cli') {
    \ServerBench\Cli\printf_and_exit("please run @ cli.\n");
}

$arguments = new CliArguments(array(
    'l:' => 'listen:',
    'd'  => 'daemonize',
    'D:' => 'dir:',
    'T:' => 'title:',
    'app:',
    'ipcs:'
));

if (isset($arguments['dir'])) {
    chdir($arguments['dir']);
}

if (!isset($arguments['listen'])) {
    \ServerBench\Cli\printf_and_exit("argument --listen or -l should be set.\n");
}

$listen_addr = explode(',', $arguments['listen']);

if (!isset($arguments['ipcs'])) {
    \ServerBench\Cli\printf_and_exit("argument --ipcs should be set.\n");
}

$ipcs = explode(',', $arguments['ipcs']);

if (count($ipcs) != 2) {
    \ServerBench\Cli\printf_and_exit("argument --ipcs should be a couple of sock files.\n");
}

$api = null;
$app_path = $arguments->get('app');

ProcessUtil::setTitle($arguments->get('title', 'serverbench<proxy>'));

try {
    if (isset($app_path) && file_exists($app_path)) {
        $api = new Api('proxy', include($app_path));
    }

    if ($api && false === $api->init()) {
        \ServerBench\syslog_error('app->init(\'proxy\') returns false.');
        exit();
    }

    PeriodicGc::enable(300);

    $proxy = new Proxy($listen_addr, $ipcs);
    $proxy->run();

    if ($api && false === $api->fini()) {
        \ServerBench\syslog_error('app->fini(\'proxy\') returns false.');
    }
} catch (Exception $e) {
    \ServerBench\syslog_error('uncaught exception from proxy: %s', [$e]);
}
