<?php
ini_set('error_reporting',    E_ALL | E_STRICT);
ini_set('display_errors',     1);
assert_options(ASSERT_ACTIVE, 1);

// xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

require __DIR__ . '/../../vendor/autoload.php';

use ServerBench\App\Client\App as ClientApp;

function mt() {
    list($usec, $sec) = explode(" ", microtime());
    return intval((float)$usec * 1000000 + (int)$sec * 1000000);
}

$app = new ClientApp();
$app->init('tcp://127.0.0.1:24816', 'json');

$start = mt();
$msg = <<<M
1234567890
1234567890
1234567890
1234567890
1234567890
1234567890
1234567890
1234567890
1234567890
1234567890
M;

for ($i = 0; $i < 100000; ++$i) {
    $rc = $app->send(array('data' => $msg, 'seq' => $i));

    if (false === $rc) {
        echo 'failed to send msg. ', $app->errno(), "\n";
        die();
    }

    $reply = $app->recv();

    if (false === $reply) {
        echo 'failed to recv msg. ', $app->errno(), "\n";
        die();
    }

    // assert($reply['seq'] == $i);
}

$end = mt();
$delta = $end - $start;
$speed = 100000 / $delta * 1000000 * 60;

echo "use {$delta} ms, speed {$speed} pkg / min \n";

// $xhprof_data = xhprof_disable('/tmp');
// $xhprof_root = '/home/ybj/Documents/Code/xhprof/';
// include_once $xhprof_root . '/xhprof_lib/utils/xhprof_lib.php';
// include_once $xhprof_root . '/xhprof_lib/utils/xhprof_runs.php';
