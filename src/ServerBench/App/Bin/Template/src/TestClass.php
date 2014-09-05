<?php
/**
 * a simple example for serverbench server app
 * which calc the md5 of message's data entry, and return it
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

use ServerBench\Logger\Logger;

class TestClass
{
    public function handleInit()
    {
        Logger::debug("handleInit");
    }

    public function handleFini()
    {
        Logger::debug("handleFini");
    }

    public function handleProcess($message)
    {
        return array('status' => 0, 'data' => md5($message['data']));
    }
}
