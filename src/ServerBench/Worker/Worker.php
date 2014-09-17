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

class Worker
{
    protected $zctx_           = NULL;
    protected $acceptor_       = NULL;

    protected $conf_ = array(
        'wait_ms'            => 30000,
        'heartbeat_interval' => 60
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

    public function heartbeat_()
    {
        $ret = false;

        try {
            $this->acceptor_->sendmulti(array('', WorkerCmd::HEARTBEAT));
            $ret = true;
        } catch (ZMQSocketException $e) {
            SysLogger::error('failed to heartbeat, ignore ...');
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
        $need_setup         = true;
        $last_heartbeat     = 0;
        $api                = $this->conf_['api'];
        $coder              = NULL;
        $handle_process     = $api->getCallable('handleProcess');

        if (isset($this->conf_['coder'])) {
            $coder = new Coder();
            $rc = $coder->setCoder($this->conf_['coder']);

            if (!$rc) {
                SysLogger::error('failed to set coder, ' . $coder->errstr());
                return false;
            }
        }

        while ($loop()) {
            if ($need_setup) {
                if ($this->acceptor_) {
                    $poller->remove($this->acceptor_);
                    $this->acceptor_ = NULL;
                }

                $rc = $this->setupSocket_();

                if (false === $rc) {
                    return false;
                }

                $poller->add($this->acceptor_, ZMQ::POLL_IN);
                $need_setup = false;
            }

            if ($need_heartbeat) {
                $rc = $this->heartbeat_();

                if (false === $rc) {
                    $need_setup = true;
                }

                $need_heartbeat = false;
                $last_heartbeat = time();
                continue;
            }

            try {
                $events = $poller->poll($readable, $writable, $wait_ms);
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

                SysLogger::error('unexpected exception: ' .
                    $e->getMessage() . '. worker exits.'
                );

                return false;
            }

            try {
                if ($events > 0) {
                    $client = $this->acceptor_->recv();
                    $empty = $this->acceptor_->recv();

                    // invalid msg
                    assert(empty($empty));

                    $message = $this->acceptor_->recv();
                    $reply   = '';
                    $resp    = NULL;

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
                }

                if (!$need_heartbeat) {
                    $now = time();

                    if ($now - $last_heartbeat > $heartbeat_interval) {
                        $need_heartbeat = true;
                    }
                }
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
