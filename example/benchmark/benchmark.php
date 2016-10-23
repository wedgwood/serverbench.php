<?php
ini_set('error_reporting',    E_ALL | E_STRICT);
ini_set('display_errors',     1);
assert_options(ASSERT_ACTIVE, 1);

require dirname(dirname(__DIR__)) . '/dist/libserverbench.phar';

use ServerBench\App\Client\Client as ClientApp;
use ServerBench\App\Client\Poller;
use ServerBench\Base\CliArguments;

$arguments = new CliArguments([
    'c:' => 'concurrent:',
    'C:' => 'connect:',
    'T:' => 'time:',
    'L:' => 'length:'
]);

function mt()
{
    return gettimeofday(true) * 1000;
}

if (!isset($arguments['concurrent']) &&
    !isset($arguments['connect']) &&
    !isset($arguments['time'])
) {
    die("usage: benchmark.php -c {num of clients} -C {address} -T {timed testing}");
}

define('NUM_OF_CLIENTS', $arguments->get('concurrent'));
define('CONNECT', $arguments->get('connect'));
define('TIME_TO_TESTING', $arguments->get('time') * 1000);
define('LENGTH', $arguments->get('length', 100));

printf(
    "clients %d, connect %s, time to testing %f sec, length %d\n",
    NUM_OF_CLIENTS,
    CONNECT,
    TIME_TO_TESTING / 1000,
    LENGTH
);

// ready
$recv = [];
$clients = [];
$poller = new Poller();

$msg = str_repeat('-', LENGTH);

for ($i = 0; $i < NUM_OF_CLIENTS; ++$i) {
    $client = new ClientApp();
    $client->setSndHwm(1000000);
    $client->setRcvHwm(1000000);
    $client->connect(CONNECT, null, true);
    $client->setRecvTimeout(200);
    $client->setSendTimeout(200);
    $id = $poller->registerReadable($client);
    $clients[$id] = $client;
    $recv[$id] = 0;
}

$sending = 0;
$recving = 0;

$start = mt();

foreach ($clients as $id => $client) {
    $client->send($msg);
}

while (mt() - $start < TIME_TO_TESTING) {
    $rset = [];
    $wset = [];

    $events = $poller->poll($rset, $wset, 0);

    if ($events > 0) {
        foreach ($rset as $id) {
            $client = $clients[$id];
            $msg = $client->recv();

            if (isset($msg) && $client->errno()) {
                echo 'failed to recv msg. ', $client->errstr(), "\n";
                die();
            }

            ++$recv[$id];
            $client->send($msg);
        }
    }
}

$end = mt();

$delta = $end - $start;
$total = array_sum($recv);
$max = max($recv);
$min = min($recv);

$speed_total = $total / $delta * 1000;
$speed_avg   = $speed_total / NUM_OF_CLIENTS;
$speed_high  = $max / $delta * 1000;
$speed_low   = $min / $delta * 1000;

echo "speed total {$speed_total} pkgs/sec\n";
echo "speed avg   {$speed_avg} pkgs/sec\n";
echo "speed high  {$speed_high} pkgs/sec\n";
echo "speed low   {$speed_low} pkgs/sec\n";
