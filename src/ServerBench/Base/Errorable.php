<?php
/**
 * trait for storing error info into instance
 *
 * @author Yuan B.J
 */

namespace ServerBench\Base;

trait Errorable
{
    private $errstr_ = '';
    private $errno_  = 0;

    public function errno()
    {
        return $this->errno_;
    }

    public function errstr()
    {
        return $this->errstr_;
    }

    protected function clearErr_()
    {
        $this->errstr_ = '';
        $this->errno_  = 0;
    }

    protected function setErr_($no, $str)
    {
        $this->errstr_ = $str;
        $this->errno_  = $no;
    }
}
