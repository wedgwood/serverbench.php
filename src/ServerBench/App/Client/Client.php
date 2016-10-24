<?php
/**
 * a simple serverbench client only with sending and recving primitive
 *
 * @author Yuan B.J.
 */

namespace ServerBench\App\Client;

use ZMQ;
use ZMQContext;
use ZMQContextException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Base\Errorable;

class Client
{
    use Errorable;

    protected $codec_  = null;
    protected $mode_   = 0;
    protected $socket_ = null;
    protected $sndhwm_ = 1000;
    protected $rcvhwm_ = 1000;

    public function setSndHwm($hwm)
    {
        $this->sndhwm_ = $hwm;
    }

    public function setRcvHwm($hwm)
    {
        $this->rcvhwm_ = $hwm;
    }

    public function connect($addr, $codec = null, $nonblocking = false)
    {
        $this->clearErr_();
        $ret = false;

        do {
            try {
                $zctx = new ZMQContext();
                $zctx->setOpt(ZMQ::CTXOPT_MAX_SOCKETS, 65535);
                $this->socket_ = $zctx->getSocket(ZMQ::SOCKET_DEALER);
                $this->socket_->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
                $this->socket_->setSockOpt(ZMQ::SOCKOPT_SNDHWM, $this->sndhwm_);
                $this->socket_->setSockOpt(ZMQ::SOCKOPT_RCVHWM, $this->rcvhwm_);
                $this->socket_->connect($addr);
            } catch (\Exception $e) {
                $this->setErr_(-1, (string)$e);
                break;
            }

            $this->codec_ = $codec;

            if ($nonblocking) {
                $this->enableNonblocking();
            } else {
                $this->disableNonblocking();
            }

            $ret = true;
        } while (0);

        return $ret;
    }

    public function enableNonblocking()
    {
        $this->mode_ = ZMQ::MODE_NOBLOCK;
    }

    public function disableNonblocking()
    {
        $this->mode_ = 0;
    }

    public function setSendTimeout($timeout)
    {
        $this->clearErr_();
        $ret = false;

        try {
            $this->socket_->setSockOpt(ZMQ::SOCKOPT_SNDTIMEO, $timeout);
            $ret = true;
        } catch (ZMQSocketException $e) {
            $this->setErr_(-1, (string)$e);
        }

        return $ret;
    }

    public function setRecvTimeout($timeout)
    {
        $this->clearErr_();
        $ret = false;

        try {
            $this->socket_->setSockOpt(ZMQ::SOCKOPT_RCVTIMEO, $timeout);
            $ret = true;
        } catch (ZMQSocketException $e) {
            $this->setErr_(-1, (string)$e);
        }

        return $ret;
    }

    public function send($data)
    {
        $this->clearErr_();
        $ret = false;

        try {
            if ($this->codec_) {
                $this->socket_->sendmulti(['', $this->codec_->encode($data)], $this->mode_);
            } else {
                $this->socket_->sendmulti(['', $data], $this->mode_);
            }

            $ret = true;
        } catch (\Exception $e) {
            $this->setErr_(-1, (string)$e);
        }

        return $ret;
    }

    public function recv()
    {
        $this->clearErr_();
        $ret = null;

        do {
            try {
                $msg = $this->socket_->recvMulti($this->mode_);

                if ($msg !== false) {
                    if (count($msg) !== 2 || !empty($msg[0])) {
                        $this->setErr_(-1, 'invalid message received.');
                        break;
                    }
                }

                if ($this->codec_) {
                    $ret = $this->codec_->decode($msg[1]);
                } else {
                    $ret = $msg[1];
                }
            } catch (\Exception $e) {
                $this->setErr_(-1, (string)$e);
            }
        } while (0);

        return $ret;
    }

    public function request($data)
    {
        $ret = false;

        if ($this->send($data)) {
            $ret = $this->recv();
        }

        return $ret;
    }

    public function getSocket()
    {
        return $this->socket_;
    }
}
