<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$console_logger = \ServerBench\Logger\ConsoleLogger::getInstance();

$console_logger->emergency('emergency');
$console_logger->alert('alert');
$console_logger->critical('critical');
$console_logger->error('error');
$console_logger->warning('warning');
$console_logger->notice('notice');
$console_logger->info('info');
$console_logger->debug('debug');

$system_logger = \ServerBench\Logger\SysLogger::getInstance();
$system_logger->setLogger($console_logger);

ServerBench\syslog('emergency', 'system emergency');
ServerBench\syslog('alert', 'system alert');
ServerBench\syslog('critical', 'system critical');
ServerBench\syslog('error', 'system error');
ServerBench\syslog('warning', 'system warning');
ServerBench\syslog('notice', 'system notice');
ServerBench\syslog('info', 'system info');
ServerBench\syslog('debug', 'system debug');
