<?php
class Res
{
    public function handleGet($data)
    {
        return array(
            'status' => 0,
            'data'   => md5($data)
        );
    }

    public function handlePost()
    {
        return array(
            'status' => 0,
            'data'   => 'post'
        );
    }

    public function handlePut()
    {
        return array(
            'status' => 0,
            'data'   => 'put'
        );
    }

    public function handleDelete()
    {
        return array(
            'status' => 0,
            'data'   => 'delete'
        );
    }
}
