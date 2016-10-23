<?php
/**
 * json codec for packing message
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Codec;

class Json
{
    public function encode($data)
    {
        return json_encode($data);
    }

    public function decode($msg)
    {
        return json_decode($msg, true);
    }
}
