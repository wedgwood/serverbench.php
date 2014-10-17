<?php
/**
 * Logger wrapper for general logging action for colorful terminal
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Logger;

use ServerBench\Console\Colorizer;

class ConsoleLogger
{
    static public function error($msg)
    {
        echo Colorizer::red($msg), "\n";
    }

    static public function info($msg)
    {
        echo Colorizer::green($msg), "\n";
    }

    static public function success($msg)
    {
        echo Colorizer::green('[success] '), $msg, "\n";
    }

    static public function failed($msg)
    {
        echo Colorizer::red('[failed] '), $msg, "\n";
    }
}
