<?php

namespace Framework\Interfaces\Http;

use Framework\http\route\Route;

/**
 *
 */
interface MiddlewareInterface
{
    public static function validate(array $middlewareRules): bool;
}
