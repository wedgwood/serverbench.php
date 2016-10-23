<?php
/**
 * trait for singleton pattern
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Base;

trait Singleton
{
    public static function getInstance()
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    protected function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
