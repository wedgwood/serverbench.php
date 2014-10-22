<?php
require '../../../vendor/autoload.php';

use ServerBench\Core\Singleton;
use ServerBench\Timer\Timer;

$timer = new Timer();

function millitime() {
    return (int)(gettimeofday(true) * 1000);
}

$timer->runAfterMs(100, function() {
    echo "\nafter 100 ms\n";
});

$timer->runAtMs((int)(millitime()) + 200, function() {
    echo "\nat 200 ms\n";
});

// $timer->runEveryMs(3000, function() {
    // echo "\nevery 3000 ms\n";
// });

while (!$timer->isEmpty()) {
    $timer->execute();
    var_dump($timer->nearestTimeMs() - (int)(gettimeofday(true) * 1000));
    usleep(100000);
}
