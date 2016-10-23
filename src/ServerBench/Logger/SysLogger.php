<?php
/**
 * Logger wrapper for general logging for serverbench's internal
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Logger;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use ServerBench\Base\Singleton;

class SysLogger
{
    use LoggerAwareTrait;
    use LoggerTrait;
    use Singleton;

    public function __construct()
    {
        $this->setLogger(ConsoleLogger::getInstance());
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, vsprintf($message, $context));
    }
}
