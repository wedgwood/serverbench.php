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
        return self::importArray($data);
    }

    /**
     * import json which's structure is ini-like
     * e.g. '{"sec_1": {"a" : "a"}, "sec_2": {"b" : "b"}}'
     */
    static public function importJsonFile($file)
    {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);

        if (json_last_error()) {
            return false;
        }

        return self::importArray($data);
    }

    static public function importArray($data)
    {
        foreach ($data as $title => $section) {
            foreach ($section as $key => $val) {
                self::set($title . '.' . $key, $val);
            }
        }

        return true;
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
