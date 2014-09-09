<?php
/**
 * abstract of process
 * which can be used to fork/term/keepalive/wait the child process
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Core;

class Process
{
    private $pid_ = NULL;
    private $title_;
    private $cb_;

    public function __construct($title = NULL)
    {
        $this->title_ = $title;
    }

    public function pid()
    {
        return $this->pid_;
    }

    public function keepalive()
    {
        $ret = false;

        if ($this->pid_) {
            if (posix_kill($this->pid_, 0)) {
                $ret = true;
            } else {
                $this->pid_ = NULL;
            }
        }

        return $ret;
    }

    public function terminate()
    {
        return $this->pid_ && posix_kill($this->pid_, SIGTERM);
    }

    public function run_($cb = NULL, $daemon = false)
    {
        $ret = false;
        $pid = pcntl_fork();

        if ($pid > 0) {
            $this->pid_ = $pid;
            $ret = $pid;
        } else if (!$pid) {
            if ($this->title_) {
                if (function_exists('cli_set_process_title')) {
                    cli_set_process_title($this->title_);
                } elseif (function_exists('setproctitle')) {
                    setproctitle($this->title_);
                } else {
                    // trace log: can not set proc title
                }
            }

            call_user_func($this->cb_);
            exit();
        }

        return $ret;
    }

    public function run($cb = NULL)
    {
        if ($this->pid_) {
            return false;
        }

        if (!$this->cb_ && $cb) {
            $this->cb_ = $cb;
        }

        if (!$this->cb_) {
            return false;
        }

        return $this->run_();
    }

    public function revive()
    {
        if ($this->keepalive()) {
            return false;
        }

        return $this->run_();
    }

    public function wait($block = false)
    {
        $ret = true;

        if ($this->pid_) {
            $rc = pcntl_waitpid($this->pid_, $status, $block ? 0 : WNOHANG);

            if ($rc <= 0) {
                $ret = false;
            } else {
                $this->pid_ = NULL;
            }
        }

        return $ret;
    }

    static public function waitAll($block = false)
    {
        $ret = false;
        $rc = pcntl_wait($status, $block ? 0 : WNOHANG);

        if ($rc > 0) {
            $ret = $rc;
        }

        return $ret;
    }
}
