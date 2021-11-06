<?php

namespace Framework\Http\Route;

use Framework\Interfaces\Http\MiddlewareInterface;
use Framework\Http\Route\Route;

class Middleware implements MiddlewareInterface
{
    public static function validate(array $middlewareRules): bool
    {
        // default geen middlewares;
        if (empty($middlewareRules)) {
            return true;
        }

        foreach ($middlewareRules as $value) {
            if (is_bool($value) && !$value) {
                return false;
            }

            if (is_array($value) && !self::validate($value)) {
                return false;
            }
        }

        return true;
    }
}
