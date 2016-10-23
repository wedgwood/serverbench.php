<?php
/**
 * programmable server implementation of serverbench's app
 *
 * @author Yuan B.J.
 */

namespace ServerBench\App\Server;

use ServerBench\Controller\Controller;
use ServerBench\Proxy\Proxy;
use ServerBench\Worker\Worker;
use ServerBench\Process\Util as ProcessUtil;

class Server
{
    private $init_callback_ = null;
    private $message_callback_ = null;
    private $process_num_ = null;
    private $ipcs_ = null;
    private $title_ = null;
    private $listen_addr_ = null;
    private $dir_ = null;
    private $sock_dir_ = null;

    public function __construct($listen_addr, $message_callback = null)
    {
        $this->listen_addr_ = $listen_addr;
        $this->message_callback_ = $message_callback;
        $this->title_ = 'serverbench';
        $this->sock_dir_ = $this->dir_ = getcwd();
    }

    public function setMessageCallback($callback)
    {
        $this->message_callback_ = $callback;
    }

    public function setInitCallback($callback)
    {
        $this->init_callback_ = $callback;
    }

    public function setTitle($title)
    {
        $this->title_ = $title;
    }

    public function setProcessNum($num)
    {
        $this->process_num_ = $num;
    }

    public function setDir($dir)
    {
        $this->dir_ = $dir;
    }

    public function setSockDir($dir)
    {
        $this->sock_dir_ = $dir;
    }

    public function run($daemon = false)
    {
        if ($daemon) {
            ProcessUtil::daemon();
        }

        $rand = mt_rand();
        $title = $this->title_;

        $ipcs = array(
            sprintf('ipc:///%s/ipc%d_%s_0.sock', $this->sock_dir_, $rand, $this->title_),
            sprintf('ipc:///%s/ipc%d_%s_1.sock', $this->sock_dir_, $rand, $this->title_)
        );

        ProcessUtil::setTitle(sprintf('%s<controller>', $title));

        PeriodicGc::enable(300);

        $controller = new Controller();

        $controller->run(
            array(
                'groups' => array(
                    array(
                        'proxy' => array(
                            'routine' => function () use ($ipcs, $title) {
                                ProcessUtil::setTitle(sprintf('%s<proxy>', $title));
                                $proxy = new Proxy($this->listen_addr_, $ipcs);
                                $proxy->run();
                            }
                        ),
                        'worker' => array(
                            'routine' => function () use ($ipcs, $title) {
                                ProcessUtil::setTitle(sprintf('%s<worker>', $title));

                                if (isset($this->init_callback_)) {
                                    if (false === call_user_func($this->init_callback_)) {
                                        \ServerBench\syslog_error('init_callback returns false.');
                                        return;
                                    }
                                }

                                $worker = new Worker($ipcs, $this->message_callback_);
                                $worker->run();
                            },
                            'num' => $this->process_num_ < 1 ? 1 : $this->process_num_
                        )
                    )
                )
            ),
            $this->dir_
        );
    }
}
