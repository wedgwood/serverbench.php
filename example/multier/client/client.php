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

define('NUM_OF_CLIENTS',  3);
define('NUM_OF_PACKAGES', 100000);

$clients = array();

for ($i = 0; $i < NUM_OF_CLIENTS; ++$i) {
    $client = new ClientApp();
    $client->init('tcp://127.0.0.1:24816', 'json');
    $client->left = NUM_OF_PACKAGES;
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

for ($i = 0; $i < NUM_OF_PACKAGES; ++$i) {
    $m = new Multier();

    for ($j = 0; $j < NUM_OF_CLIENTS; ++$j) {
        $rc = $clients[$j]->send(array('data' => $msg, 'seq' => $j));

        if (false === $rc) {
            echo 'failed to send msg. ', $clients[$j]->errno(), "\n";
            die();
        }
    }

    $reply = $m->fetch($clients, 1000);

    if (false === $reply) {
        echo 'multier failed to recv msg. ', $m->errstr(), "\n";
        die();
    }

    for ($j = 0; $j < NUM_OF_CLIENTS; ++$j) {
        if (!$reply[$j]) {
            echo 'app1 failed to recv msg. ', $clients[$j]->errno(), "\n";
            die();
        }
    }
}

$end = mt();
$delta = $end - $start;
$speed = NUM_OF_PACKAGES / $delta * 1000000 * NUM_OF_CLIENTS;

echo "use {$delta} ms, speed {$speed} pkg / sec \n";
