<?php
/**
 * default worker implemention of the serverbench
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Worker;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Process\Loop;
use ServerBench\Process\Util;
use ServerBench\Timer\Timer;

class Worker
{
    private $message_callback_ = null;
    private $max_wait_ms_ = 40;
    private $zctx_ = null;
    private $ipc0_ = null;
    private $ipc1_ = null;

    public function __construct($ipcs, $message_callback)
    {
        $this->message_callback_ = $message_callback;
        $zctx = new ZMQContext(1, false);
        $this->zctx_ = $zctx;

        $ipc0 = new ZMQSocket($zctx, ZMQ::SOCKET_PULL);
        $ipc0->setSockOpt(ZMQ::SOCKOPT_RCVHWM, 0);
        $ipc0->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
        $ipc0->connect($ipcs[0]);
        $this->ipc0_ = $ipc0;

        $ipc1 = new ZMQSocket($zctx, ZMQ::SOCKET_PUSH);
        $ipc1->setSockOpt(ZMQ::SOCKOPT_SNDHWM, 0);
        $ipc1->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
        $ipc1->connect($ipcs[1]);
        $this->ipc1_ = $ipc1;
    }

    public function setMaxWaitMs($ms)
    {
        $this->max_wait_ms_ = $ms;
    }

    public function run()
    {
        $ipc0 = $this->ipc0_;
        $ipc1 = $this->ipc1_;

        $timer = Timer::getInstance();
        $loop = Loop::getInstance();

        $poller = new ZMQPoll();
        $poller->add($ipc0, ZMQ::POLL_IN);

        while ($loop->running()) {
            // not execute timer too many times
            $timer->execute(64);

            $readable = [];
            $writable = [];

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

                if ($events <= 0) {
                    continue;
                }
            } catch (ZMQPollException $e) {
                if (4 == $e->getCode()) {
                    continue;
                }

                throw $e;
            }

            while (true) {
                try {
                    $msg = $ipc0->recvMulti(ZMQ::MODE_NOBLOCK);

                    if (false === $msg) {
                        // would block
                        break;
                    }

                    if (count($msg) !== 3 || !empty($msg[1])) {
                        break;
                    }

                    try {
                        $reply = call_user_func($this->message_callback_, $msg[2]);
                    } catch (\Exception $e) {
                        \ServerBench\syslog_error('caught exception from message_callback: %s', [$e]);
                        continue;
                    }

                    $rc = false;

                    do {
                        try {
                            $rc = $ipc1->sendmulti([$msg[0], '', $reply]);
                        } catch (ZMQPollException $e) {
                            if (4 == $e->getCode()) {
                                continue;
                            }

                            throw $e;
                        }

                        break;
                    } while (true);

                    if (false === $rc) {
                        \ServerBench\syslog_error(
                            'replying\'s mq of worker is out of memory, msg(%s) would lose',
                            [$reply]
                        );
                    }
                } catch (ZMQSocketException $e) {
                    if (4 == $e->getCode()) {
                        continue;
                    }

                    throw $e;
                }
            }
        }
    }

    public function __destruct()
    {
        $this->ipc0_     = null;
        $this->ipc1_     = null;
        $this->zctx_     = null;
    }
}
