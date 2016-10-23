<?php

/**
 * i/o multiplexing tool for serverbench's clients
 *
 * @author Yuan B.J.
 */

namespace ServerBench\App\Client;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

class Poller
{
    private $poller_ = null;

    public function __construct()
    {
        $this->poller_ = new ZMQPoll();
    }

    public function registerReadable($client)
    {
        $socket = $client->getSocket();
        $hash = spl_object_hash($socket);
        $this->poller_->add($socket, ZMQ::POLL_IN);
        return $hash;
    }

    public function unregister($client)
    {
        $this->poller_->remove($client->getSocket());
    }

    public function registerWritable($client)
    {
        $socket = $client->getSocket();
        $hash = spl_object_hash($socket);
        $this->poller_->add($socket, ZMQ::POLL_OUT);
        return $hash;
    }

    public function registerAllEvents($client)
    {
        $socket = $client->getSocket();
        $hash = spl_object_hash($socket);
        $this->poller_->add($socket, ZMQ::POLL_IN | ZMQ::POLL_OUT);
        return $hash;
    }

    public function poll(&$rset = [], &$wset = [], $ms = -1)
    {
        $readable = [];
        $writable = [];
        $events = $this->poller_->poll($readable, $writable, $ms);

        if ($events > 0) {
            foreach ($readable as $socket) {
                $rset[] = spl_object_hash($socket);
            }

            foreach ($writable as $socket) {
                $wset[] = spl_object_hash($socket);
            }
        }

        return $events;
    }

    public function count()
    {
        return $this->poller_->count();
    }
}
