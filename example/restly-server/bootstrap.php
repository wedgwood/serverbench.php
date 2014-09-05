<?php
ini_set('error_reporting',    E_ALL | E_STRICT);
ini_set('display_errors',     1);
assert_options(ASSERT_ACTIVE, 1);
date_default_timezone_set('PRC');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/src/TestClass.php';

use ServerBench\App\Server\App as ServerApp;

$app = new ServerApp();
$app->bootstrap();
