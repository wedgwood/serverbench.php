<?php
/**
 * worker process of the serverbench
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Worker;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Constant\WorkerCmd;
use ServerBench\Core\Loop;
use ServerBench\Logger\SysLogger;
use ServerBench\Message\Coder;
use ServerBench\Timer\Timer;

class Worker
{
    protected $zctx_     = NULL;
    protected $acceptor_ = NULL;

    protected $conf_ = array(
        'wait_ms'            => 30000,
        'heartbeat_interval' => 60,
        'worker_load_max'    => 10
    );

    public function __construct()
    {
        $this->zctx_ = new ZMQContext();
    }

    public function setupSocket_()
    {
        $ret = false;
        $loop = Loop::getInstance();

        while ($loop()) {
            $acceptor = new ZMQSocket($this->zctx_, ZMQ::SOCKET_DEALER);

            try {
                $acceptor->connect($this->conf_['acceptor']);
                $acceptor->sendmulti(array('', WorkerCmd::READY));
                $this->acceptor_ = $acceptor;
                $ret = true;
                SysLogger::info('connect succeeds');
            } catch (ZMQSocketException $e) {
                SysLogger::error('failed to setup socket, retry ...');
                sleep(5);
                continue;
            }

            break;
        }

        return $ret;
    }

    public function run_()
    {
        $loop = Loop::getInstance();

        $poller             = new ZMQPoll();
        $readable           = array();
        $writable           = array();
        $wait_ms            = $this->conf_['wait_ms'];
        $heartbeat_interval = $this->conf_['heartbeat_interval'];
        $need_heartbeat     = false;
        $api                = $this->conf_['api'];
        $coder              = NULL;
        $handle_process     = $api->getCallable('handleProcess');
        $timer              = Timer::getInstance()->clear();

        if (isset($this->conf_['coder'])) {
            $coder = new Coder();
            $rc = $coder->setCoder($this->conf_['coder']);

            if (!$rc) {
                SysLogger::error('failed to set coder, ' . $coder->errstr());
                return false;
            }
        }

        $rc = $this->setupSocket_();

        if (false === $rc) {
            return false;
        }

        $poller->add($this->acceptor_, ZMQ::POLL_IN);

        $timer->runEvery($heartbeat_interval, function() use(&$need_heartbeat) {
            if ($need_heartbeat) {
                SysLogger::debug('worker do heartbeat ...');

                try {
                    $this->acceptor_->sendmulti(array('', WorkerCmd::HEARTBEAT));
                } catch (ZMQSocketException $e) {
                    SysLogger::error('failed to heartbeat, ignore ...');
                }
            } else {
                $need_heartbeat = true;
            }
        });

        $continue_recv = true;

        while ($loop()) {
            if (!$continue_recv) {
                try {
                    if ($timer->isEmpty()) {
                        $events = $poller->poll($readable, $writable, $wait_ms);
                    } else {
                        $nearest_delta_time = $timer->nearestDeltaTimeMs();

                        if ($wait_ms > 0 && $wait_ms < $nearest_delta_time) {
                            $real_wait_ts = $wait_ms;
                        } else {
                            if ($nearest_delta_time < 0) {
                                $nearest_delta_time = 0;
                            }

                            $real_wait_ts = $nearest_delta_time;
                        }

                        $events = $poller->poll(
                            $readable,
                            $writable,
                            $real_wait_ts
                        );
                    }

                    $errors = $poller->getLastErrors();

                    if (count($errors) > 0) {
                        foreach ($errors as $error) {
                            SysLogger::error('error polling ' . $error);
                        }
                    }

                    // not execute timer too many times
                    $timer->execute(50);

                    if ($events <= 0) {
                        continue;
                    }
                } catch (ZMQPollException $e) {
                    if (4 == $e->getCode()) {
                        continue;
                    }

                    SysLogger::error('unexpected exception: ' .
                        $e->getMessage() . '. worker exits.'
                    );

                    return false;
                }

            }

            try {
                $client = $this->acceptor_->recv(ZMQ::MODE_NOBLOCK);

                if (false === $client) {
                    $continue_recv = false;
                    continue;
                }

                $empty = $this->acceptor_->recv();

                // invalid msg
                assert(empty($empty));

                $message = $this->acceptor_->recv();
                $reply   = '';
                $resp    = NULL;
                $continue_recv = true;

                try {
                    $data = NULL;

                    if ($coder) {
                        $data = $coder->unpack($message);
                    } else {
                        $data = $message;
                    }

                    $resp = call_user_func($handle_process, $data);
                } catch (Exception $e) {
                    SysLogger::error(
                        'exception catched from handleProcess: ' .
                        $e->getMessage()
                    );
                }

                if ($resp !== false) {
                    if ($coder) {
                        $reply = $coder->pack($resp);
                    }
                }

                $this->acceptor_->sendmulti(array('', $client, '', $reply));
                $need_heartbeat = false;
            } catch (ZMQSocketException $e) {
                if (4 == $e->getCode()) {
                    continue;
                }

                SysLogger::error('unexpected exception: ' .
                    $e->getMessage() . '. worker exits.'
                );

                return false;
            }
        }
    }

    public function run($conf)
    {
        $this->conf_ = array_merge($this->conf_, $conf);

        $api = $this->conf_['api'];

        if (!$api->exists('handleProcess')) {
            SysLogger::error("handleProcess has not been implemented, worker exits");
            return false;
        }

        if ($api->exists('handleInit') && false === $api->call('handleInit')) {
            SysLogger::error("handleInit return false, worker exits");
            return false;
        }

        $this->run_();

        if ($api->exists('handleFini')) {
            $api->call('handleFini');
        }
    }
}
