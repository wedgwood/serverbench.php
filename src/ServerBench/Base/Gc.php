<?php
/**
 * gc wrapper class
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Base;

class Gc
{
    public static function enable()
    {
        return gc_enable();
    }

    public static function disable()
    {
        return gc_disable();
    }

    public static function enabled()
    {
        return gc_enabled();
    }

    public static function trigger()
    {
        return gc_collect_cycles();
    }
}
