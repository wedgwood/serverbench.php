<?php
/**
 * a simple serverbench rest-like client
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\App\Client;

use ServerBench\App\Constant\Restly;
use ServerBench\App\Constant\RestlyMethod;

class RestlyApp extends App
{
    public function get($res, $data)
    {
        $request = array(
            Restly::METHOD => RestlyMethod::GET,
            Restly::RES    => $res,
            Restly::DATA   => $data
        );

        return $this->send($request);
    }

    public function post($res, $data)
    {
        $request = array(
            Restly::METHOD => RestlyMethod::POST,
            Restly::RES    => $res,
            Restly::DATA   => $data
        );

        return $this->send($request);
    }

    public function put($res, $data)
    {
        $request = array(
            Restly::METHOD => RestlyMethod::PUT,
            Restly::RES    => $res,
            Restly::DATA   => $data
        );

        return $this->send($request);
    }

    public function delete($res, $data)
    {
        $request = array(
            Restly::METHOD => RestlyMethod::DELETE,
            Restly::RES    => $res,
            Restly::DATA   => $data
        );

        return $this->send($request);
    }
}
