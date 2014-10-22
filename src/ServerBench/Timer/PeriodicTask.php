<?php
namespace ServerBench\Timer;

class PeriodicTask extends Task
{
    private $interval_ = 0;

    public function __construct($interval, $cb)
    {
        $this->interval_ = $interval;
        parent::__construct((int)(gettimeofday(true) * 1000) + $this->interval_, $cb);
    }

    public function run()
    {
        parent::run();
    }

    public function nextTime()
    {
        $this->ts += $this->interval_;
        return $this;
    }
}
