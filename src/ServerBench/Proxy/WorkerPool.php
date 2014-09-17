<?php
/**
 * abstract of worker queue
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Proxy;

class WorkerPool
{
    private $pool_ = array();

    public function push($worker)
    {
        return array_push($this->pool_, $worker);
    }

    public function pop()
    {
        return array_shift($this->pool_);
    }

    public function count()
    {
        return count($this->pool_);
    }

    public function isEmpty()
    {
        return empty($this->pool_);
    }
}
