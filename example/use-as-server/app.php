<?php

class App
{
    public function init()
    {
        return true;
    }

    public function fini()
    {
        return true;
    }

    public function process($msg)
    {
        return $msg;
    }
}

return new App;
