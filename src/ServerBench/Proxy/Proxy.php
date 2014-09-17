<?php
/**
 * proxy front of serverbench, responsing for binding server socket,
 * and recving message
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Proxy;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Constant\WorkerCmd;
use ServerBench\Core\Loop;
use ServerBench\Logger\ConsoleLogger;
use ServerBench\Logger\SysLogger;
use ServerBench\Proxy\WorkerPool;

class Proxy
{
    public function run($conf)
    {
        $api = $conf['api'];

        if ($api->exists('handleInit') && false === $api->call('handleInit')) {
            return false;
        }

        $zctx = new ZMQContext();
        $acceptor = new ZMQSocket($zctx, ZMQ::SOCKET_ROUTER);

        try {
            foreach ($conf['acceptor'] as $frontend) {
                $acceptor->bind($frontend);
            }

            $connector = new ZMQSocket($zctx, ZMQ::SOCKET_ROUTER);
            $connector->bind($conf['connector']);
        } catch (ZMQSocketException $e) {
            ConsoleLogger::error(sprintf('[!]failed to bind. (%s)', $e->getMessage()));
            return false;
        }

        $handle_input = $api->exists('handleInput') ?
            $api->getCallable('handleInput') : NULL;

        $worker_pool = new WorkerPool();

        $writable = array();
        $readable = array();

        $poller = new ZMQPoll();
        $poller->add($connector, ZMQ::POLL_IN);

        $loop = Loop::getInstance();

        while ($loop()) {
            try {
                $events = $poller->poll($readable, $writable);
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
                    $e->getMessage() . '. proxy exits.'
                );

                return false;
            }

            if ($events > 0) {
                foreach ($readable as $socket) {
                    if ($socket === $connector) {
                        while (true) {
                            try {
                                $worker = $socket->recv(ZMQ::MODE_NOBLOCK);

                                if (false === $worker) {
                                    break;
                                }

                                $worker_pool->push($worker);

                                if (1 == $worker_pool->count()) {
                                    $poller->add($acceptor, ZMQ::POLL_IN);
                                }

                                $empty = $socket->recv(ZMQ::MODE_NOBLOCK);

                                assert(empty($empty));

                                if (!empty($empty)) {
                                    // invalid msg
                                    continue;
                                }

                                $client = $socket->recv(ZMQ::MODE_NOBLOCK);

                                if ($client != WorkerCmd::READY &&
                                    $client != WorkerCmd::HEARTBEAT
                                ) {
                                    // return to client
                                    $empty = $socket->recv(ZMQ::MODE_NOBLOCK);
                                    assert(empty($empty));

                                    if (!empty($empty)) {
                                        // invalid msg
                                        continue;
                                    }

                                    $message = $socket->recv(ZMQ::MODE_NOBLOCK);

                                    $acceptor->sendmulti(
                                        array($client, '', $message),
                                        ZMQ::MODE_NOBLOCK
                                    );
                                }
                            } catch (ZMQSocketException $e) {
                                if (4 == $e->getCode()) {
                                    continue;
                                }

                                SysLogger::error('unexpected exception: ' .
                                    $e->getMessage() . '. proxy exits.'
                                );

                                return false;
                            }
                        }
                    } elseif ($worker_pool->count()) {
                        while (true) {
                            $client = $socket->recv(ZMQ::MODE_NOBLOCK);

                            if (false === $client) {
                                break;
                            }

                            $empty = $socket->recv(ZMQ::MODE_NOBLOCK);
                            assert(empty($empty));

                            $message = $socket->recv(ZMQ::MODE_NOBLOCK);

                            // if ($handle_input) {
                                // if (false === call_user_func($handle_input, $message)) {
                                // }
                            // }

                            $connector->sendmulti(
                                array(
                                    $worker_pool->pop(),
                                    $client,
                                    '',
                                    $message
                                )
                            );

                            if ($worker_pool->isEmpty()) {
                                $poller->remove($acceptor);
                                break;
                            }
                        }
                    } else {
                        // no thing to do ...
                    }
                }
            }
        }

        $api->exists('handleFini') && $api->call('handleFini');
    }
}
