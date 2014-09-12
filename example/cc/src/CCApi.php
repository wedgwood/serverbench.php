<?php
/**
 * an implemention of configure center
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.11
 */

use ServerBench\Logger\Logger;

use ServerBench\App\Constant\Restly;
use ServerBench\App\Constant\RestlyMethod;
use ServerBench\App\Server\RestlyApi;
use ServerBench\App\Util\Config;

require __DIR__ . '/CC.php';

class CCApi extends RestlyApi
{
    public function handleInit()
    {
        Logger::debug("handleInit");

        $cc = new \CC();
        $cc->init(
            Config::get('my.host'),
            Config::get('my.user'),
            Config::get('my.passwd'),
            Config::get('my.db')
        );

        $this->addRoute('cc',         'get',    array($cc, 'handleGet'));
        $this->addRoute('cc',         'put',    array($cc, 'handlePut'));
        $this->addRoute('cc',         'delete', array($cc, 'handleDelete'));
        $this->addRoute('cc/version', 'get',    array($cc, 'handleGetVersion'));
    }

    public function handleFini()
    {
        Logger::debug("handleFini");
    }
}
