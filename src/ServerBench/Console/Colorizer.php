<?php
/**
 * colorizer msg using ansi color code
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Console;

class Colorizer
{
    const OFF    = "\033[0m";
    const BLACK  = "\033[30m";
    const RED    = "\033[31m";
    const GREEN  = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE   = "\033[34m";
    const PURPLE = "\033[35m";
    const CYAN   = "\033[36m";
    const WHITE  = "\033[37m";

    static protected function color_($c, $msg)
    {
        return $c . $msg . self::OFF;
    }

    static public function black($msg)
    {
        return self::color_(self::BLACK, $msg);
    }

    static public function red($msg)
    {
        return self::color_(self::RED, $msg);
    }

    static public function green($msg)
    {
        return self::color_(self::GREEN, $msg);
    }

    static public function yellow($msg)
    {
        return self::color_(self::YELLOW, $msg);
    }

    static public function blue($msg)
    {
        return self::color_(self::BLUE, $msg);
    }

    static public function purple($msg)
    {
        return self::color_(self::PURPLE, $msg);
    }

    static public function cyan($msg)
    {
        return self::color_(self::CYAN, $msg);
    }

    static public function white($msg)
    {
        return self::color_(self::WHITE, $msg);
    }
}
