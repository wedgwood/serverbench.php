<?php
ini_set('error_reporting',    E_ALL);
ini_set('display_errors',     1);
assert_options(ASSERT_ACTIVE, 1);

require __DIR__ . '/../../../vendor/autoload.php';

use ServerBench\App\Client\RestlyApp as ClientApp;

class CCAgent
{
    private $change_ = array();
    private $keys_   = NULL;
    private $latest_ = 0;
    private $client_ = NULL;

    public function init($addr, $keys)
    {
        $ret = false;
        $client = new ClientApp();

        if ($client->init($addr, 'json')) {
            $this->keys_ = $keys;
            $this->client_ = $client;
            $ret = true;
        }

        return $ret;
    }

    public function printConf($conf)
    {
        foreach ($conf as $k => $v) {
            printf("%5s => %s\n", $k, $v[0]);
        }
    }

    public function pull_()
    {
        $reply = $this->client_->call('get', 'cc', $this->keys_);
        assert($reply);

        $version = $this->latest_;

        foreach ($reply['data'] as $k => $v) {
            if ($version < $v[1]) {
                $version = $v[1];
            }
        }

        $this->latest_ = $version;
        echo "\n=====\n";
        $this->printConf($reply['data']);
        echo "\n=====\n";
    }

    public function loop()
    {
        $this->pull_();

        while (true) {
            $reply = $this->client_->call('get', 'cc/version', $this->keys_);
            assert($reply);

            if (!$reply['status']) {
                if ($reply['data'] > $this->latest_) {
                    $this->pull_();
                }
            }

            sleep(10);
        }
    }
}

$agent = new CCAgent();
$agent->init('tcp://127.0.0.1:24816', array('a', 'b'));
$agent->loop();
