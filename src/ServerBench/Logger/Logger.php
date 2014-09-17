<?php
/**
 * Logger wrapper for general logging action
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Logger;

class Logger
{
    static protected $logger_;

    static public function setLogger($logger)
    {
        static::$logger_ = $logger;
    }

    static public function getLogger()
    {
        return static::$logger_;
    }

    static public function fatal($msg)
    {
        static::$logger_->fatal($msg);
    }

    static public function emergency($msg)
    {
        static::$logger_->emergency($msg);
    }

    static public function alert($msg)
    {
        static::$logger_->alert($msg);
    }

    static public function critical($msg)
    {
        static::$logger_->critical($msg);
    }

    static public function error($msg)
    {
        static::$logger_->error($msg);
    }

    static public function notice($msg)
    {
        static::$logger_->notice($msg);
    }

    static public function warning($msg)
    {
        static::$logger_->warning($msg);
    }

    static public function info($msg)
    {
        static::$logger_->info($msg);
    }

    static public function debug($msg)
    {
        static::$logger_->debug($msg);
    }
}
