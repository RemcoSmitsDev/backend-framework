<?php

namespace Framework\Http\Route;

use Framework\Container\DependencyInjector;
use Framework\Interfaces\Http\MiddlewareInterface;

class Middleware implements MiddlewareInterface
{
    /**
     * @var array|null
     */
    private static ?array $route;

    /**
     * @param array $route
     * @param array $middlewareRules
     *
     * @return bool
     */
    public static function validate(array $route, array $middlewareRules): bool
    {
        // set route
        self::$route = $route;

        // default geen middlewares;
        if (empty($middlewareRules)) {
            return true;
        }

        // loop through all the middlewaress
        foreach ($middlewareRules as $value) {
            // when is bool
            if (is_bool($value) && !$value) {
                return false;
            }

            // class/callable
            if (is_string($value) && !self::validateStringRule($value)) {
                return false;
            }

            if (is_array($value) && !self::validate($route, $value)) {
                return false;
            }
        }

        return true;
    }

    private static function validateStringRule(string $rule): bool
    {
        // when is callable
        if (is_callable($rule)) {
            return $rule();
        }

        // when is class exists
        if (class_exists($rule)) {
            // next anomynouse function
            $next = function () use ($rule) {
                return $rule;
            };

            // call method with dependencies injection
            // validate rule with response
            return DependencyInjector::resolve($rule, 'handle')->with(['route' => self::$route, 'next' => $next])->getContent() === $rule;
        }

        return false;
    }
}
