<?php
/**
 * process loop
 * ignore noice signal, and detect term/quit signal
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Process;

use \ServerBench\Base\Singleton;

class Loop
{
    use Singleton;

    private $running_;
    private $started_;

    protected function __construct()
    {
        $this->reset();

        $signal = Signal::getInstance();
        $signal->on(SIGHUP, SIG_IGN);
        $signal->on(SIGPIPE, SIG_IGN);
        $signal->on(SIGINT, [$this, 'stop']);
        $signal->on(SIGQUIT, [$this, 'stop']);
        $signal->on(SIGTERM, [$this, 'stop']);

        $this->start();
    }

    public function running()
    {
        Signal::getInstance()->dispatch();
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
