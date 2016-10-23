<?php
/**
 * Logger wrapper for general logging action for colorful terminal
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Logger;

use Psr\Log\AbstractLogger;
use ServerBench\Base\Singleton;
use ServerBench\Console\Colorizer;

class ConsoleLogger extends AbstractLogger
{
    use Singleton;

    protected $colors_ = [
        'emergency' => 'red',
        'alert'     => 'red',
        'critical'  => 'red',
        'error'     => 'red',
        'warning'   => 'yellow',
        'notice'    => 'yellow',
        'info'      => 'green',
        'debug'     => 'blue'
    ];

    public function log($level, $message, array $context = [])
    {
        echo Colorizer::color($this->colors_[$level], sprintf("%-9s", $level)),
            "\t", vsprintf($message, $context), "\n";
    }
}
