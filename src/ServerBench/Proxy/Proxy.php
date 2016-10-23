<?php
/**
 * default proxy front implemention of serverbench, responsing for binding server socket, and recving message
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Proxy;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Logger\SysLogger;
use ServerBench\Process\Loop;
use ServerBench\Process\Util;
use ServerBench\Timer\Timer;

class Proxy
{
    private $max_wait_ms_ = 40;
    private $zctx_ = null;
    private $acceptor_ = null;
    private $ipc0_ = null;
    private $ipc1_ = null;

    public function setMaxWaitMs($ms)
    {
        $this->max_wait_ms_ = $ms;
    }

    public function __construct($listen_addr, $ipcs)
    {
        $zctx = new ZMQContext(1, false);
        $this->zctx_ = $zctx;
        $acceptor = new ZMQSocket($zctx, ZMQ::SOCKET_ROUTER);

        $acceptor->setSockOpt(ZMQ::SOCKOPT_RCVHWM, 1024 * 1024 * 16);
        $acceptor->setSockOpt(ZMQ::SOCKOPT_SNDHWM, 1024 * 1024 * 32);
        $acceptor->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);

        foreach ((array)$listen_addr as $item) {
            $acceptor->bind($item);
        }

        $this->acceptor_ = $acceptor;

        $ipc0 = new ZMQSocket($zctx, ZMQ::SOCKET_PUSH);
        $ipc0->setSockOpt(ZMQ::SOCKOPT_HWM, 1024 * 1024 * 16);
        $ipc0->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
        $ipc0->bind($ipcs[0]);
        $this->ipc0_ = $ipc0;

        $ipc1 = new ZMQSocket($zctx, ZMQ::SOCKET_PULL);
        $ipc1->setSockOpt(ZMQ::SOCKOPT_HWM, 1024 * 1024 * 16);
        $ipc1->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
        $ipc1->bind($ipcs[1]);
        $this->ipc1_ = $ipc1;
    }

    public function run()
    {
        $acceptor = $this->acceptor_;
        $ipc0 = $this->ipc0_;
        $ipc1 = $this->ipc1_;

        $timer = Timer::getInstance();

        $poller = new ZMQPoll();
        $poller->add($acceptor, ZMQ::POLL_IN);
        $poller->add($ipc1, ZMQ::POLL_IN);

        $loop = Loop::getInstance();
        $loop->start();

        while ($loop->running()) {
            // not execute timer too many times
            $timer->execute(64);

            $writable = [];
            $readable = [];

            try {
                if ($timer->isEmpty()) {
                    $events = $poller->poll($readable, $writable, $this->max_wait_ms_);
                } else {
                    $nearest_delta_ms = $timer->nearestDeltaTimeMs();

                    if ($this->max_wait_ms_ > 0 && $this->max_wait_ms_ < $nearest_delta_ms) {
                        $real_wait_ms = $this->max_wait_ms_;
                    } else {
                        if ($nearest_delta_ms < 0) {
                            $nearest_delta_ms = 0;
                        }

                        $real_wait_ms = $nearest_delta_ms;
                    }

                    $events = $poller->poll($readable, $writable, $real_wait_ms);
                }
            } catch (ZMQPollException $e) {
                if (4 == $e->getCode()) {
                    continue;
                }

                throw $e;
            }

            if ($events > 0) {
                foreach ($readable as $socket) {
                    if ($socket === $ipc1) {
                        while (true) {
                            try {
                                $msg = $socket->recvMulti(ZMQ::MODE_NOBLOCK);

                                if (false === $msg) {
                                    // would block
                                    break;
                                }

                                if (count($msg) !== 3 || !empty($msg[1])) {
                                    \ServerBench\syslog_error('invalid msg(%s) from worker.', [var_export($msg, true)]);
                                    break;
                                }

                                $rc = false;

                                do {
                                    try {
                                        $rc = $acceptor->sendmulti([$msg[0], '', $msg[2]], ZMQ::MODE_NOBLOCK);
                                    } catch (ZMQSocketException $e) {
                                        if (4 == $e->getCode()) {
                                            continue;
                                        }

                                        throw $e;
                                    }

                                    break;
                                } while (true);

                                if (false === $rc) {
                                    \ServerBench\syslog_error(
                                        'sending\'s mq is out of memory, msg(%s) would lose',
                                        [$msg[2]]
                                    );
                                }
                            } catch (ZMQSocketException $e) {
                                if (4 == $e->getCode()) {
                                    continue;
                                }

                                throw $e;
                            }
                        }
                    } elseif ($socket === $acceptor) {
                        // acceptor
                        for ($i = 0; $i < 4096; ++$i) {
                            try {
                                $msg = $acceptor->recvMulti(ZMQ::MODE_NOBLOCK);

                                if (false === $msg) {
                                    break;
                                }

                                if (count($msg) !== 3 || !empty($msg[1])) {
                                    \ServerBench\syslog_error('invalid msg(%s) from client.', [var_export($msg, true)]);
                                    break;
                                }

                                $rc = false;

                                do {
                                    try {
                                        $rc = $ipc0->sendmulti([$msg[0], '', $msg[2]], ZMQ::MODE_NOBLOCK);
                                    } catch (ZMQSocketException $e) {
                                        if (4 == $e->getCode()) {
                                            continue;
                                        }
                                    }

                                    break;
                                } while (true);

                                if (false === $rc) {
                                    \ServerBench\syslog_error(
                                        'ipc0\'s mq is out of memory, msg(%s) would lose.',
                                        [var_export($msg, true)]
                                    );
                                }
                            } catch (ZMQSocketException $e) {
                                if (4 == $e->getCode()) {
                                    continue;
                                }

                                throw $e;
                            }
                        }
                    } else {
                        // unexpected branch.
                    }
                }
            }
        }
    }

    public function __destruct()
    {
        $this->ipc0_     = null;
        $this->ipc1_     = null;
        $this->acceptor_ = null;
        $this->zctx_     = null;
    }
}
