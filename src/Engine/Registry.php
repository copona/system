<?php

namespace Copona\System\Engine;

use Illuminate\Container\Container;

final class Registry extends Container
{
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    protected function __construct(){}

    private function __clone(){}

    private function __wakeup(){}

    public static function get($key, $parameters = [])
    {
        return self::getInstance()->make($key, $parameters);
    }

    public function set($key, $value)
    {
        self::getInstance()->instance($key, $value);
    }
}