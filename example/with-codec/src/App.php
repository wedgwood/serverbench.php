<?php

use ServerBench\Codec\Decorator as CodecDecorator;
use ServerBench\Codec\Json as JsonCodec;

function realProcess($req)
{
    return $req;
}

class App
{
    protected $real_process_ = null;

    public function __construct()
    {
        $this->real_process_ = new CodecDecorator(new JsonCodec(), realProcess);
    }

    public function init()
    {
        return true;
    }

    public function fini()
    {
        return true;
    }

    public function process($msg)
    {
        return call_user_func($this->real_process_, $msg);
    }
}
