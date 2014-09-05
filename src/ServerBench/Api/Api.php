<?php
/**
 * api wrapper package
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Api;

use ServerBench\Core\Errorable;

class Api extends Errorable
{
    private $handler_ = array();

    public function register($name, $handler = NULL)
    {
        if (is_array($name)) {
            foreach ($name as $e => $handler) {
                $this->handler_[$e] = $handler;
            }
        } else {
            $this->handler_[$name] = $handler;
        }
    }

    public function import($class)
    {
        $ret = true;

        try {
            $r = new \Reflectionclass($class);
        } catch (\LogicException $e) {
            $this->setErr_($e->getCode(), $e->getMessage());
            $ret = false;
        } catch (\ReflectionException $e) {
            $this->setErr_($e->getCode(), $e->getMessage());
            $ret = false;
        }

        if (!$ret) {
            return false;
        }

        $obj = new $class();
        $methods = $r->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            $this->handler_[$method->getName()] = array($obj, $method->getName());
        }

        return $ret;
    }

    public function exists($name)
    {
        return isset($this->handler_[$name]);
    }

    public function isEmpty()
    {
        return empty($this->handler_);
    }

    public function getCallable($name)
    {
        return $this->handler_[$name];
    }

    public function call($name)
    {
        return call_user_func_array(
            $this->handler_[$name],
            array_slice(func_get_args(), 1)
        );
    }
}
