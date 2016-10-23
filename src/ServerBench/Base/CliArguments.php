<?php
/**
 * using for parsing of cli's arguments
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Base;

class CliArguments extends \ArrayObject
{
    public function __construct($options)
    {
        $opts = array();
        $longopts = array();
        $relations = array();

        foreach ($options as $k => $v) {
            if (is_numeric($k)) {
                if (strlen($v) == 1) {
                    $opts[] = $v;
                } else {
                    $longopts[] = $v;
                }
            } else {
                $opts[] = $k;
                $longopts[] = $v;
                $k = trim($k, ':');
                $v = trim($v, ':');
                $relations[$k] = $v;
                $relations[$v] = $k;
            }
        }

        $arguments = array();

        foreach (getopt(implode('', $opts), $longopts) as $k => $v) {
            if (isset($relations[$k]) && !isset($arguments[$relations[$k]])) {
                $arguments[$relations[$k]] = $v;
            }

            $arguments[$k] = $v;
        }

        parent::__construct($arguments, \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS);
    }

    public function get($option, $default = null)
    {
        return isset($this[$option]) ? $this[$option] : $default;
    }
}
