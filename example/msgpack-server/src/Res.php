<?php
/**
 * a simple example for serverbench server rest-like app
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

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
