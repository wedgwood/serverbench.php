<?php
/**
 * periodic gc
 *
 * @author Yuan B.J.
 */

namespace ServerBench\App\Server;

use \ServerBench\Base\Gc;
use \ServerBench\Timer\Timer;

class PeriodicGc
{
    private static $task_ = null;

    public static function enable($sec)
    {
        Gc::enable();
        self::$task_ = Timer::getInstance()->runEvery($sec, function () {
            Gc::trigger();
        });
    }

    public static function disable()
    {
        self::$task_->cancel();
        Gc::disable();
    }
}
