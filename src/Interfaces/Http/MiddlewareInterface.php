<?php

namespace Framework\Interfaces\Http;

/**
 *
 */
interface MiddlewareInterface
{
    public static function validate(array $middlewareRules): bool;
}
