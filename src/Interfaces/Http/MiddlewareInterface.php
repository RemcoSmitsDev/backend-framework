<?php

namespace Framework\Interfaces\Http;

/**
 *
 */
interface MiddlewareInterface
{
    /**
     * @param array $route
     * @param array $middlewareRules
     * @return bool
     */
    public static function validate(array $route, array $middlewareRules): bool;
}
