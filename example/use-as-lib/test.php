<?php

require dirname(dirname(__DIR__)) . '/dist/libserverbench.phar';

$server = new \ServerBench\App\Server\Server('tcp://127.0.0.1:12345', function ($msg) {
    return $msg;
});

$server->setProcessNum(10);
$server->run();
