<?php
$phar = new Phar('serverbench.phar');
$phar->buildFromDirectory(__DIR__, '/\.php$/');
$phar->stopBuffering();
$phar->setStub($phar->createDefaultStub('./vendor/autoload.php'));
