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
    const WORKER_LOAD_MAX = 10;
    private $pool_ = array();

    public function push($worker)
    {
        if (isset($this->pool_[$worker]) &&
            $this->pool_[$worker] < self::WORKER_LOAD_MAX
        ) {
            ++$this->pool_[$worker][1];
        } else {
            $this->pool_[$worker] = array($worker, self::WORKER_LOAD_MAX);
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
