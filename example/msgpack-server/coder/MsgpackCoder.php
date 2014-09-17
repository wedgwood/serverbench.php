<?php
if (!extension_loaded('msgpack')) {
    exit('extension[msgpack] not installed');
}

class MsgpackCoder
{
    static public function pack($data)
    {
        return msgpack_pack($data);
    }

    static public function unpack($msg)
    {
        return msgpack_unpack($msg);
    }
}
