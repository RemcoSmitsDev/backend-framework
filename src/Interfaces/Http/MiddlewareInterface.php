<?php

namespace Framework\Interfaces\Http;

/**
 *
 */
interface MiddlewareInterface
{
    /**
     * @param array $middlewareRules
     * @return bool
     */
    public static function validate(array $middlewareRules): bool;
}
