<?php
ini_set('error_reporting',    E_ALL | E_STRICT);
ini_set('display_errors',     1);
assert_options(ASSERT_ACTIVE, 1);

require __DIR__ . '/../../../vendor/autoload.php';

use ServerBench\App\Client\App as ClientApp;
use ServerBench\App\Client\Multier;

function mt() {
    list($usec, $sec) = explode(" ", microtime());
    return intval((float)$usec * 1000000 + (int)$sec * 1000000);
}

$app1 = new ClientApp();
$app1->init('tcp://127.0.0.1:24816', 'json');

$app2 = new ClientApp();
$app2->init('tcp://127.0.0.1:24816', 'json');

$app3 = new ClientApp();
$app3->init('tcp://127.0.0.1:24816', 'json');


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
    $m = new Multier();

    $rc = $app1->send(array('data' => $msg, 'seq' => $i));

    if (false === $rc) {
        echo 'failed to send msg. ', $app1->errno(), "\n";
        die();
    }

    $rc = $app2->send(array('data' => $msg, 'seq' => $i));

    if (false === $rc) {
        echo 'failed to send msg. ', $app2->errno(), "\n";
        die();
    }

    $rc = $app3->send(array('data' => $msg, 'seq' => $i));

    if (false === $rc) {
        echo 'failed to send msg. ', $app3->errno(), "\n";
        die();
    }

    $reply = $m->fetch(array($app1, $app2, $app3), 400);

    if (false === $reply) {
        echo 'multier failed to recv msg. ', $m->errstr(), "\n";
        die();
    }

    if (!$reply[0]) {
        echo 'app1 failed to recv msg. ', $app1->errno(), "\n";
        die();
    }

    if (!$reply[1]) {
        echo 'app2 failed to recv msg. ', $app2->errno(), "\n";
        die();
    }

    if (!$reply[2]) {
        echo 'app3 failed to recv msg. ', $app3->errno(), "\n";
        die();
    }
}

$end = mt();
$delta = $end - $start;
$speed = 100000 / $delta * 1000000 * 60 * 3;

echo "use {$delta} ms, speed {$speed} pkg / min \n";
