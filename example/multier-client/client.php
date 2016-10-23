<?php
ini_set('error_reporting',    E_ALL | E_STRICT);
ini_set('display_errors',     1);
assert_options(ASSERT_ACTIVE, 1);

require dirname(dirname(__DIR__)) . '/dist/libserverbench.phar';

use ServerBench\App\Client\Client as ClientApp;
use ServerBench\App\Client\Multier;

function mt()
{
    return gettimeofday(true) * 1000;
}

define('NUM_OF_CLIENTS', 200);
define('NUM_OF_PACKAGES', 10000);

$clients = [];

for ($i = 0; $i < NUM_OF_CLIENTS; ++$i) {
    $client = new ClientApp();
    $client->setSndHwm(1000000);
    $client->setRcvHwm(1000000);
    $client->connect('tcp://127.0.0.1:12345', new ServerBench\Codec\Json(), true);
    $client->setRecvTimeout(200);
    $client->setSendTimeout(200);
    $clients[] = $client;
}

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

$start = mt();
$sending = 0;
$recving = 0;
$m = new Multier();
$map = [];

function doRecv($block = false)
{
    global $clients, $m, $cnt, $recving, $map;

    $reply = $m->fetch($clients, $block ? -1 : 0);

    if (false === $reply) {
        echo 'multier failed to recv msg. ', $m->errstr(), "\n";
        die();
    }

    if (empty($reply)) {
        return;
    }

    for ($i = 0; $i < NUM_OF_CLIENTS; ++$i) {
        if (!isset($reply[$i]) && $clients[$i]->errno()) {
            echo 'failed to recv msg. ', $clients[$i]->errstr(), "\n";
            die();
        }

        if (isset($reply[$i])) {
            unset($map[$reply[$i]['seq']]);
            ++$recving;
        }
    }
}

while ($sending < NUM_OF_PACKAGES) {
    $j = $sending % NUM_OF_CLIENTS;
    $rc = $clients[$j]->send(['data' => $msg, 'seq' => $sending]);

    if (false === $rc) {
        printf(
            "failed to send msg. %d:%s\n",
            $clients[$j]->errno(),
            $clients[$j]->errstr()
        );
        die();
    } else {
        $map[$sending] = 1;
        ++$sending;
    }

    if ($sending % 10 == 0) {
        doRecv();
    }
}

while ($sending > $recving) {
    printf("\rrecving(%d) < $sending(%d)", $recving, $sending);
    doRecv(true);
}

printf("\rrecving(%d) == $sending(%d)\n", $recving, $sending);

$end = mt();
$delta = $end - $start;
$speed = NUM_OF_PACKAGES / $delta * 1000;
$mps   = $speed * 100 / 1024 / 1024;

echo "use {$delta} ms, speed {$speed} pkgs/sec, {$mps} mb/sec\n";
