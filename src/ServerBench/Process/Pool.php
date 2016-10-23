<?php
/**
 * process pool
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Process;

class Pool
{
    private $workers_ = [];
    private $num_;
    private $routine_;

    public function fork()
    {
        $ret = false;
        $pid = pcntl_fork();

        if ($pid > 0) {
            $meta = new Meta();
            $meta->begin_ts = time();
            $this->workers_[$pid] = $meta;
        }

        return $pid;
    }

    public function spawn($path, $args = [], $envs = [])
    {
        $ret = false;
        $pid = $this->fork();

        if (0 === $pid) {
            if (false === pcntl_exec($path, $args, $envs)) {
                exit(0);
            }
        } elseif ($pid > 0) {
            $ret = $pid;
        } else {
            // nothing to do ...
        }

        return $ret;
    }

    private function recycle_($pid)
    {
        unset($this->workers_[$pid]);
    }

    public function waitAll($block = false)
    {
        foreach ($this->workers_ as $pid => $worker) {
            $pid = pcntl_waitpid($pid, $status, $block ? 0 : WNOHANG);

            if ($pid > 0) {
                $this->recycle_($pid);
            }
        }
    }

    public function killAll($sig)
    {
        foreach ($this->workers_ as $pid => $meta) {
            posix_kill($pid, $sig);
        }
    }

    public function cleanUp()
    {
        foreach ($this->workers_ as $pid => $meta) {
            if (!posix_kill($pid, 0)) {
                unset($this->workers_[$pid]);
            }
        }
    }

    public function terminate($block = true)
    {
        $this->cleanUp();
        $this->killAll(SIGTERM);
        $this->waitAll($block);
    }

    public function keep()
    {
        $this->waitAll(false);
        $this->cleanUp();

        $need = $this->num_ - count($this->workers_);

        for ($i = 0; $i < $need; ++$i) {
            call_user_func($this->routine_);
        }
    }

    public function start($num, $routine)
    {
        if (is_callable($routine)) {
            $this->routine_ = function () use ($routine) {
                if (0 === $this->fork()) {
                    $routine();
                    exit(0);
                }
            };
        } elseif (is_string($routine)) {
            $this->routine_ = function () use ($routine) {
                $this->spawn($routine);
            };
        } elseif (is_array($routine)) {
            $this->routine_ = function () use ($routine) {
                call_user_func_array([$this, 'spawn'], $routine);
            };
        } else {
            return false;
        }

        $this->num_ = $num;
        $this->keep();
    }

    public function getWorkersNum($real = true)
    {
        if ($real) {
            return count($this->workers_);
        }

        return $this->num_;
    }

    public function getWorkersDied()
    {
        return $this->num_ - count($this->workers_);
    }
}
