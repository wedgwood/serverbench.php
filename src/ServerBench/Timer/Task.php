<?php
namespace ServerBench\Timer;

class Task
{
    public $ts         = 0;
    private $cb_       = NULL;
    private $canceled_ = false;

    public function __construct($ts, $cb)
    {
        $this->ts  = $ts;
        $this->cb_ = $cb;
    }

    public function run()
    {
        call_user_func($this->cb_);
    }

    public function cancel()
    {
        $this->canceled_ = true;
    }

    public function canceled()
    {
        return $this->canceled_;
    }
}
