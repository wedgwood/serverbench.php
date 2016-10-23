<?php
$lib = __DIR__ . '/dist/libserverbench.phar';
$bin = __DIR__ . '/dist/serverbench.phar';
@unlink($lib);
@unlink($bin);

$phar = new Phar($lib);
$phar->startBuffering();
$phar->buildFromDirectory(__DIR__, '$(src|vendor)/.*|(logo|version)\.txt$');
// $phar->compressFiles(Phar::GZ);
$phar->setStub($phar->createDefaultStub('./vendor/autoload.php'));
$phar->stopBuffering();

$phar = new Phar($bin);
$phar->startBuffering();
$phar->buildFromDirectory(__DIR__, '$(src|vendor)/.*|(logo|version)\.txt$');
// $phar->compressFiles(Phar::GZ);
$phar->setStub($phar->createDefaultStub('./src/ServerBench/cli/cli.php'));
$phar->stopBuffering();
