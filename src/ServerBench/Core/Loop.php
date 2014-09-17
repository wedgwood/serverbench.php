<?php
/**
 * process loop
 * ignore noice signal, and detect term/quit signal
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Core;

class Loop extends Singleton
{
    private $running_;
    private $started_;

    protected function __construct()
    {
        $this->reset();
        $int_handler = array($this, 'stop');
        pcntl_signal(SIGHUP,  SIG_IGN);
        pcntl_signal(SIGPIPE, SIG_IGN);
        pcntl_signal(SIGINT,  $int_handler, false);
        pcntl_signal(SIGQUIT, $int_handler, false);
        pcntl_signal(SIGTERM, $int_handler, false);
    }

    public function running()
    {
        pcntl_signal_dispatch();
        return $this->running_;
    }

    public function stop()
    {
        $this->running_ = false;
    }

    public function start()
    {
        $this->started_ = true;
        $this->running_ = true;
        return $this;
    }

    public function reset()
    {
        $this->started_ = false;
        $this->running_ = false;
        return $this;
    }

    public function __invoke()
    {
        if (!$this->started_) {
            $this->start();
        }

        return $this->running();
    }
}
