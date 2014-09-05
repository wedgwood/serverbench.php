<?php
/**
 * general config access for serverbench app,
 * it reads ini format file now.
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\App\Util;

class Config
{
    private static $data_ = array();

    static public function importIniFile($file)
    {
        $data = parse_ini_file($file, true);
        self::importArray($data);
    }

    static public function importArray($data)
    {
        foreach ($data as $title => $section) {
            foreach ($section as $key => $val) {
                self::set($title . '.' . $key, $val);
            }
        }
    }

    static public function set($key, $val)
    {
        self::$data_[$key] = $val;
    }

    static public function get($key, $default = NULL)
    {
        return array_key_exists($key, self::$data_) ?
            self::$data_[$key] : $default;
    }
}
