<?php
/**
 * app's api wrapper for severbench
 *
 * @author Yuan B.J.
 */

namespace ServerBench\App\Server;

use InvalidArgumentException;
use ServerBench\Codec\Decorator as CodecDecorator;

class Api
{
    private $as_;
    private $init_;
    private $process_;
    private $fini_;

    public function __construct($as, $app, $codec = null)
    {
        $this->as_ = $as;

        if (is_object($app) && method_exists($app, 'process')) {
            if (method_exists($app, 'init')) {
                $this->init_ = [$app, 'init'];
            }

            if (method_exists($app, 'fini')) {
                $this->init_ = [$app, 'fini'];
            }

            if (method_exists($app, 'process')) {
                if (isset($codec)) {
                    $this->process_ = new CodecDecorator($codec, [$app, 'process']);
                } else {
                    $this->process_ = [$app, 'process'];
                }
            } else {
                throw InvalidArgumentException('app\'s process method must be set.');
            }
        } elseif (is_callable($app)) {
            $this->process_ = $app;
        } else {
            throw InvalidArgumentException('no callable or object for app\'s implementation found.');
        }
    }

    public function init()
    {
        $ret = true;

        if (isset($this->init_)) {
            $ret = call_user_func($this->init_, $this->as_);
        }

        return $ret;
    }

    public function fini()
    {
        $ret = true;

        if (isset($this->fini_)) {
            $ret = call_user_func($this->fini_, $this->as_);
        }

        return $ret;
    }

    public function process($message)
    {
        return call_user_func($this->process_, $message);
    }
}
