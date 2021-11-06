<?php

namespace Framework\Interfaces\Http;

/**
 *
 */
interface RoutesInterface
{
    public static function get(string $route, \Closure $callback): self;

    public static function post(string $route, \Closure $callback): self;

    public static function update(string $route, \Closure $callback): self;

    public static function delete(string $route, \Closure $callback): self;
}
