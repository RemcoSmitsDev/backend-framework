<?php

namespace Framework\Interfaces\Http;

/**
 *
 */
interface RoutesInterface
{
    public function get(string $route, \Closure|array $action): self;

    public function post(string $route, \Closure|array $action): self;

    public function put(string $route, \Closure|array $action): self;

    public function patch(string $route, \Closure|array $action): self;

    public function delete(string $route, \Closure|array $action): self;

    public function middleware(bool|array $validateRules): self;

    public function prefix(string $prefix): self;

    public function group(\Closure $action);

    public function pattern(array $patterns): self;

    public function name(string $routeName): self;

    public function getRouteByName(string $routeName, array $params = []): string;

    public function getCurrentRoute(): array;
}