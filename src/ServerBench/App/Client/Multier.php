<?php
/**
 * muliter for multi request handling
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.10
 */

namespace ServerBench\App\Client;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Constant\WorkerCmd;
use ServerBench\Core\Errorable;
use ServerBench\Core\Loop;
use ServerBench\Logger\ConsoleLogger;
use ServerBench\Logger\SysLogger;
use ServerBench\Proxy\WorkerPool;

class Multier extends Errorable
{
    public function fetch($clients, $timeout_ms = 0)
    {
        $poller        = new ZMQPoll();
        $socket2client = new \SplObjectStorage();
        $ret           = NULL;

        foreach ($clients as $key => $client) {
            $s = $client();
            $socket2client[$s] = array($client, $key);
            $poller->add($s, ZMQ::POLL_IN);
        }

        $ms_left = $timeout_ms;
        $ret     = array_fill(0, count($clients), NULL);

        do {
            $readable = array();
            $writable = array();

            try {
                if ($ms_left > 0) {
                    $tv1_ms = gettimeofday(true) * 1000;
                    $events = $poller->poll($readable, $writable, $ms_left);
                    $tv2_ms = gettimeofday(true) * 1000;
                    $ms_left -= ($tv2_ms - $tv1_ms);

                    if ($ms_left < 0) {
                        $ms_left = 0;
                    }
                } else {
                    $events = $poller->poll($readable, $writable, $ms_left);
                }

                $errors = $poller->getLastErrors();

                if (count($errors) > 0) {
                    $errno  = -1;
                    $errstr = array();

                    foreach ($errors as $error) {
                        $errstr[] = $error;
                    }

                    $this->setErr_($errno, implode(',', $errstr));
                    $ret = false;
                }
            } catch (ZMQPollException $e) {
                $this->setErr_($e->getCode(), $e->getMessage());
                $ret = false;
            }

            if (false === $ret) {
                break;
            }

            if ($events > 0) {
                foreach ($readable as $s) {
                    $client = $socket2client[$s][0];
                    $key = $socket2client[$s][1];

                    try {
                        $msg = $client->recv();
                        $ret[$key] = $msg;
                    } catch (ZMQSocketException $e) {
                        $res[$key] = false;
                    }

                    $poller->remove($s);
                }
            }
        } while ($ms_left != 0 && $poller->count());

        return $ret;
    }
}
