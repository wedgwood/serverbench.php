<?php
/**
 * php codec for packing message
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Codec;

class Php
{
    public function encode($data)
    {
        return serialize($data);
    }

    public function decode($msg)
    {
        return unserialize($msg);
    }
}
