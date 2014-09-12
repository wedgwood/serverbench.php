<?php
/**
 * a simple serverbench client only with sending and recving primitive
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\App\Client;

use ZMQ;
use ZMQContext;
use ZMQSocket;
use ZMQSocketException;
use ZMQContextException;

use ServerBench\Core\Errorable;
use ServerBench\Message\Coder;

class App extends Errorable
{
    protected $zctx_   = NULL;
    protected $client_ = NULL;
    protected $coder_  = NULL;
    protected $mode_   = 0;

    public function __construct()
    {
        $this->zctx_ = new ZMQContext();
    }

    public function enableNonblocking()
    {
        $this->mode_ = ZMQ::MODE_NOBLOCK;
    }

    public function disableNonblocking()
    {
        $this->mode_ = 0;
    }

    public function setSendTimeout($t)
    {
        if (!$this->client_) {
            return false;
        }

        $ret = false;

        try {
            $this->client_->setOpt(ZMQ::SOCKOPT_SNDTIMEO, $t);
            $ret = true;
        } catch (ZMQContextException $e) {
            $this->setErr_($e->getCode(), $e->getMessage());
        }

        return $ret;
    }

    public function setRecvTimeout($t)
    {
        if (!$this->client_) {
            return false;
        }

        $ret = false;

        try {
            $this->client_->setOpt(ZMQ::SOCKOPT_RCVTIMEO, $t);
            $ret = true;
        } catch (ZMQContextException $e) {
            $this->setErr_($e->getCode(), $e->getMessage());
        }

        return $ret;
    }

    public function init($addr, $coder = NULL)
    {
        $ret = true;
        $this->client_ = $this->zctx_->getSocket(ZMQ::SOCKET_DEALER);
        // $this->client_ = $this->zctx_->getSocket(ZMQ::SOCKET_REQ);

        try {
            $this->client_->connect($addr);

            if ($coder) {
                $c = new Coder();
                $rc = $c->setCoder($coder);

                if (!$rc) {
                    $this->setErr_($c->errno(), $c->errstr());
                    $ret = false;
                } else {
                    $this->coder_ = $c;
                }
            }
        } catch (ZMQSocketException $e) {
            $this->setErr_($e->getCode(), $e->getMessage());
            $ret = false;
        }

        return $ret;
    }

    public function send($data)
    {
        $ret  = false;

        try {
            if ($this->coder_) {
                $this->client_->sendmulti(
                    array('', $this->coder_->pack($data)),
                    $this->mode_
                );
                // $this->client_->send($this->coder_->pack($data), $this->mode_);
            } else {
                $this->client_->sendmulti(array('', $data), $this->mode_);
                // $this->client_->send($data, $this->mode_);
            }

            $ret = true;
        } catch (ZMQSocketException $e) {
            $this->setErr_($e->getCode(), $e->getMessage());
        }

        return $ret;
    }

    public function recv()
    {
        $ret = false;

        try {
            $empty = $this->client_->recv($this->mode_);
            assert(empty($empty));
            $msg = $this->client_->recv($this->mode_);

            if ($this->coder_) {
                $ret = $this->coder_->unpack($msg);
            } else {
                $ret = $msg;
            }
        } catch (ZMQSocketException $e) {
            $this->setErr_($e->getCode(), $e->getMessage());
        }

        return $ret;
    }

    public function request($data)
    {
        $ret = false;
        $rc = $this->send($data);

        if ($rc) {
            $ret = $this->recv();
        }

        return $ret;
    }

    public function __invoke()
    {
        return $this->client_;
    }
}
