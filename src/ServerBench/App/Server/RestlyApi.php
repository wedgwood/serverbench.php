<?php
/**
 * rest-like api wrapper for serverbench
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\App\Server;

use ServerBench\App\Constant\Restly;
use ServerBench\Logger\ConsoleLogger;

class RestlyApi
{
    protected $callmap_ = array();

    public function addRoute($res, $method, $cb)
    {
        if (!isset($this->callmap_[$res])) {
            $this->callmap_[$res] = array();
        }

        $this->callmap_[$res][$method] = $cb;
        return true;
    }

    public function notexists($data)
    {
        return false;
    }

    public function handleProcess($data)
    {
        if (!isset($data[Restly::RES])) {
            return false;
        }

        if (!isset($data[Restly::METHOD])) {
            return false;
        }

        $res = $data[Restly::RES];
        $method = $data[Restly::METHOD];

        if (!isset($this->callmap_[$res])) {
            return $this->notexists();
        }

        if (!isset($this->callmap_[$res][$method])) {
            return false;
        }

        $reply = call_user_func(
            $this->callmap_[$res][$method],
            isset($data[Restly::DATA]) ? $data[Restly::DATA] : NULL
        );

        return $reply;
    }
}
