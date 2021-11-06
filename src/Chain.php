<?php

namespace Framework;

class Chain
{
    private static $returnValue;
    private static $callback;

    public function __construct($returnValue, $callback)
    {
        self::$returnValue = $returnValue;
        self::$callback = $callback;
    }

    public static function chain()
    {
        return self::$returnValue;
    }

    public function __destruct()
    {
        // call callback
        (self::$callback)();
    }
}
