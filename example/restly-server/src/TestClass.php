<?php

use ServerBench\Logger\Logger;

use ServerBench\App\Constant\Restly;
use ServerBench\App\Constant\RestlyMethod;
use ServerBench\App\Server\RestlyApi;

require __DIR__ . '/Res.php';

class TestClass extends RestlyApi
{
    public function handleInit()
    {
        Logger::debug("handleInit");

        $res = new \Res();
        $this->addRoute('res', 'get',    array($res, 'handleGet'));
        $this->addRoute('res', 'post',   array($res, 'handlePost'));
        $this->addRoute('res', 'put',    array($res, 'handlePut'));
        $this->addRoute('res', 'delete', array($res, 'handleDelete'));
    }

    public function handleFini()
    {
        Logger::debug("handleFini");
    }
}
