<?php
ini_set('error_reporting',    E_ALL | E_STRICT);
ini_set('display_errors',     1);
assert_options(ASSERT_ACTIVE, 1);

require __DIR__ . '/../../../vendor/autoload.php';

use ServerBench\App\Client\RestlyApp as ClientApp;

function mt() {
    list($usec, $sec) = explode(" ", microtime());
    return intval((float)$usec + (int)$sec * 1000000);
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
    $rc = $app->get('res', $msg);

    if (false === $rc) {
        echo 'failed to send msg. ', $app->errno(), "\n";
        die();
    }

    $reply = $app->recv();

    if (false === $reply) {
        echo 'failed to recv msg. ', $app->errno(), "\n";
        die();
    }

    assert($reply['data'] == md5($msg));
}

$end = mt();
$delta = $end - $start;
$speed = 100000 / $delta * 1000000;

echo "use {$delta} ms, speed {$speed} pkg / sec \n";
