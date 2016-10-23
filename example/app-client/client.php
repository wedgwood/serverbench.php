<?php
ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);
assert_options(ASSERT_ACTIVE, 1);

require dirname(dirname(__DIR__)) . '/dist/libserverbench.phar';

use ServerBench\App\Client\Client as ClientApp;

function mt()
{
    return gettimeofday(true) * 1000;
}

$app = new ClientApp();
$app->connect('tcp://127.0.0.1:12345', new ServerBench\Codec\Json(), false);

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

for ($i = 0; $i < 1000; ++$i) {
    $rc = $app->send(['data' => $msg, 'seq' => $i]);

    if (false === $rc) {
        echo 'failed to send msg. ', $app->errno(), "\n";
        die();
    }

    $reply = $app->recv();

    if (false === $reply) {
        echo 'failed to recv msg. ', $app->errno(), "\n";
        die();
    }

    assert($reply['seq'] == $i);
}

$end = mt();
$delta = $end - $start;
$speed = 1000 / $delta * 1000;

echo "use {$delta} ms, speed {$speed} pkgs/s \n";
