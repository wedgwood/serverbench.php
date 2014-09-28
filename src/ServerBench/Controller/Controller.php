<?php
/**
 * Controller of serverbench
 * which boot worker group and proxy, and monitor them
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Controller;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Constant\ControllerCmd;
use ServerBench\Core\Loop;
use ServerBench\Core\Process;
use ServerBench\Logger\SysLogger;
use ServerBench\Proxy\Proxy;
use ServerBench\Worker\Worker;

class Controller
{
    private $conf_    = array();
    private $proxy_   = array();
    private $worker_  = array();

    private function runWorker_($conf)
    {
        $ret = false;
        $process = new Process(isset($conf['name']) ? $conf['name'] : NULL);

        $pid = $process->run(function() use($conf) {
            Loop::getInstance()->reset();
            $worker = new Worker();
            $worker->run($conf);
        });

        if ($pid) {
            $ret = $process;
        }

        return $ret;
    }

    private function runProxy_($conf)
    {
        $ret = false;
        $process = new Process(isset($conf['name']) ? $conf['name'] : NULL);

        $pid = $process->run(function() use($conf) {
            Loop::getInstance()->reset();
            $proxy = new Proxy();
            $proxy->run($conf);
        });

        if ($pid) {
            $ret = $process;
        }

        return $ret;
    }

    private function runGroup_($conf)
    {
        $proxy = $this->runProxy_($conf['proxy']);

        if (false === $proxy) {
            SysLogger::error('failed to start the proxy, serverbench exits');
            return false;
        }

        $this->proxy_[] = $proxy;

        for ($i = 0; $i < $conf['worker']['num']; ++$i) {
            $worker = $this->runWorker_($conf['worker']);

            if (false === $worker) {
                SysLogger::error('failed to start the worker, retry after sec');
            }

            $this->worker_[] = $worker;
        }

        return true;
    }

    public function run($conf)
    {
        $this->conf_ = $conf;
        $conf = $conf['controller'];

        if (isset($conf['name'])) {
            if (function_exists('cli_set_process_title')) {
                cli_set_process_title($conf['name']);
            } elseif (function_exists('setproctitle')) {
                setproctitle($conf['name']);
            } else {
                SysLogger::notice('failed to set proc title');
            }
        }

        $rc = $this->runGroup_($this->conf_);

        if (false === $rc) {
            return false;
        }

        $zctx = new ZMQContext(1, false);
        $poller = new ZMQPoll();

        $cmd_socket = new ZMQSocket($zctx, ZMQ::SOCKET_REP);
        $cmd_socket->bind($conf['acceptor']);
        $poller->add($cmd_socket, ZMQ::POLL_IN);

        $readable = array();
        $writable = array();

        $loop = Loop::getInstance();

        while ($loop()) {
            try {
                $events = $poller->poll($readable, $writable, 40000);
                $errors = $poller->getLastErrors();

                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        SysLogger::error('error polling ' . $error);
                    }
                }
            } catch (ZMQPollException $e) {
                if (4 == $e->getCode()) {
                    continue;
                }

                throw $e;
            }

            if ($events > 0) {}

            SysLogger::debug('controller do keepalive...');

            foreach ($this->worker_ as $process) {
                if ($process->keepalive()) {
                    continue;
                } else {
                    SysLogger::error(sprintf(
                        'worker[%d] has died', $process->pid()
                    ));
                }

                $rc = $process->revive();

                if ($rc) {
                    SysLogger::error(sprintf(
                        'worker[%d] has been revived successfully',
                        $process->pid()
                    ));
                } else {
                    SysLogger::error('worker has been revived failed!');
                }
            }

            foreach ($this->proxy_ as $process) {
                if ($process->keepalive()) {
                    continue;
                } else {
                    SysLogger::error(sprintf(
                        'proxy[%d] has died', $process->pid()
                    ));
                }

                $rc = $process->revive();

                if ($rc) {
                    SysLogger::error(sprintf(
                        'proxy[%d] has been revived successfully',
                        $process->pid()
                    ));
                } else {
                    SysLogger::error('proxy has been revived failed!');
                }
            }

            while (Process::waitAll()) {}
        }

        foreach ($this->worker_ as $pid => $process) {
            while ($process->keepalive()) {
                $process->terminate();

                if (!$process->wait(false)) {
                    usleep(40);
                    continue;
                }

                break;
            }
        }

        foreach ($this->proxy_ as $pid => $process) {
            if ($process->keepalive()) {
                $process->terminate();

                if (!$process->wait(false)) {
                    usleep(40);
                    continue;
                }

                break;
            }
        }
    }
}
