<?php
/**
 * controller of serverbench which manage process of proxy pool and worker pool
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Controller;

use ZMQ;
use ZMQContext;
use ZMQPoll;
use ZMQPollException;
use ZMQSocket;
use ZMQSocketException;

use ServerBench\Process\Loop;
use ServerBench\Process\Pool;
use ServerBench\Process\Util;
use ServerBench\Process\Signal;

class Controller
{
    public function run($conf)
    {
        $groups = [];

        foreach ($conf['groups'] as $group_conf) {
            $group = [];

            if (isset($group_conf['proxy'])) {
                $proxy_conf = $group_conf['proxy'];
                $proxy_pool = new Pool();
                $proxy_pool->start(1, $proxy_conf['routine']);
                $group['proxy'] = $proxy_pool;
            }

            if (isset($group_conf['worker'])) {
                $worker_conf = $group_conf['worker'];
                $worker_pool = new Pool();
                $worker_pool->start($worker_conf['num'], $worker_conf['routine']);
                $group['worker'] = $worker_pool;
            }

            $groups[] = $group;
        }

        sleep(1);
        $bootstrap_success = true;

        foreach ($groups as $group) {
            foreach ($group as $pool) {
                $pool->waitAll();

                if ($pool->getWorkersDied() > 0) {
                    $bootstrap_success = false;
                    break;
                }
            }
        }

        if ($bootstrap_success) {
            $loop = Loop::getInstance();
            $loop->start();

            $restarting_workers = false;

            Signal::getInstance()->on(SIGUSR1, function () use (&$restarting_workers) {
                $restarting_workers = true;
            });

            while ($loop->running()) {
                if ($restarting_workers) {
                    foreach ($groups as $group) {
                        if (isset($group['worker'])) {
                            $group['worker']->killAll(SIGTERM);
                        }
                    }

                    $restarting_workers = false;
                }

                foreach ($groups as $group) {
                    foreach ($group as $pool) {
                        $pool->keep();
                    }
                }

                usleep(40000);
            }
        } else {
            \ServerBench\syslog_error('failed to bootstrap workers in controller.');
        }

        foreach ($groups as $group) {
            foreach ($group as $pool) {
                $pool->terminate();
            }
        }
    }
}
