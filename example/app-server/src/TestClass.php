<?php
use ServerBench\Logger\Logger;

class TestClass
{
    private $data_ = array();

    public function handleInit()
    {
        Logger::debug("handleInit");
    }

    public function handleFini()
    {
        Logger::debug("handleFini");
    }

    public function handleProcess($data)
    {
        return array(
            'status' => 0,
            'data' => md5($data['data']),
            'seq' => $data['seq']
        );
    }
}
