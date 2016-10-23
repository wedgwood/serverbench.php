<?php
/**
 * timer of the serverbench
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Timer;

use ServerBench\Base\Singleton;

class Timer
{
    use Singleton;

    private $queue_ = null;

    public function __construct()
    {
        $this->queue_ = new TaskQueue();
    }

    public function isEmpty()
    {
        return $this->queue_->isEmpty();
    }

    public function runAt($ts, $cb)
    {
        return $this->runAtMs($ts * 1000, $cb);
    }

    public function runAtMs($ts, $cb)
    {
        $task = new Task($ts, $cb);
        $this->queue_->insert($task);
        return $task;
    }

    public function runAfter($interval, $cb)
    {
        return $this->runAfterMs($interval * 1000, $cb);
    }

    public function runAfterMs($interval, $cb)
    {
        $task = new Task((int)(gettimeofday(true) * 1000) + $interval, $cb);
        $this->queue_->insert($task);
        return $task;
    }

    public function runEvery($interval, $cb)
    {
        return $this->runEveryMs($interval * 1000, $cb);
    }

    public function runEveryMs($interval, $cb)
    {
        $task = new PeriodicTask($interval, $cb);
        $this->queue_->insert($task);
        return $task;
    }

    public function nearestTime()
    {
        return (int)($this->nearestTimeMs() / 1000);
    }

    public function nearestTimeMs()
    {
        return $this->queue_->isEmpty() ? null : $this->queue_->top()->ts;
    }

    public function nearestDeltaTime()
    {
        return (int)($this->nearestDeltaTimeMs() / 1000);
    }

    public function nearestDeltaTimeMs()
    {
        return $this->nearestTimeMs() - (int)(gettimeofday(true) * 1000);
    }

    public function execute($max = PHP_INT_MAX)
    {
        $now = (int)(gettimeofday(true) * 1000);
        $i = 1;

        while (!$this->queue_->isEmpty()) {
            $task = $this->queue_->top();

            if ($task->ts <= $now) {
                $this->queue_->extract();

                if (!$task->canceled()) {
                    $task->run();

                    if ($task instanceof PeriodicTask) {
                        $this->queue_->insert($task->nextTime());
                    }
                }

                if (++$i > $max) {
                    break;
                }
            } else {
                break;
            }
        }
    }

    public function clear()
    {
        $this->queue_ = new TaskQueue();
        return $this;
    }
}
