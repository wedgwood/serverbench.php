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
    private $worker_load_max_;

    public function __construct($worker_load_max)
    {
        $this->worker_load_max_ = $worker_load_max;
    }

    public function push($worker)
    {
        if (isset($this->pool_[$worker]) &&
            $this->pool_[$worker] < $this->worker_load_max_
        ) {
            ++$this->pool_[$worker][1];
        } else {
            $this->pool_[$worker] = array($worker, $this->worker_load_max_);
        }
    }

    public function pop()
    {
        $ele    = array_shift($this->pool_);
        $worker = $ele[0];

        if ($ele[1] > 1) {
            --$ele[1];
            $this->pool_[$worker] = $ele;
        }

        return $worker;
    }

    public function isEmpty()
    {
        return empty($this->pool_);
    }
}
