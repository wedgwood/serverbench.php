<?php
/**
 * message coder instance
 * which including json/php/plain coder, any one could add any other coder
 * plugin to it
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Message;

use ServerBench\Core\Errorable;

class Coder extends Errorable
{
    private $coder_ = NULL;

    public function setCoder($coder)
    {
        $ret = true;

        if (is_string($coder)) {
            switch ($coder) {
                case 'json':
                    $this->coder_  = array(
                        'pack'    => 'json_encode',
                        'unpack'  => function($msg) {
                            return json_decode($msg, true);
                        }
                    );
                    break;
                case 'php':
                    $this->coder_  = array(
                        'pack'    => 'serialize',
                        'unpack'  => 'unserialize'
                    );
                    break;
                case 'plain':
                    $this->coder_  = array(
                        'pack'    => function($data) {
                            return (string)$data;
                        },
                        'unpack'  => function($msg) {
                            return $msg;
                        }
                    );
                    break;
                default:
                    $this->setErr_(-1, sprintf('[%s] not supported', $coder));
                    $ret = false;
            }
        } elseif (class_exists($coder)) {
            if (!method_exists($coder, 'pack') ||
                !method_exists($coder, 'unpack')
            ) {
                $this->setErr_(-1, 'custom coder misses methods[pack,unpack]');
                $ret = false;
            }

            $this->coder_  = array(
                'pack'    => array($coder, 'pack'),
                'unpack'  => array($coder, 'unpack')
            );
        } else {
            $this->setErr_(-1, 'no valid coder given');
            $ret = false;
        }

        return $ret;
    }

    public function pack($data)
    {
        return call_user_func($this->coder_['pack'], $data);
    }

    public function unpack($msg)
    {
        return call_user_func($this->coder_['unpack'], $msg);
    }
}
