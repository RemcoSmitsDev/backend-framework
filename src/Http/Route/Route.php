<?php

namespace Framework\Http\Route;

use Framework\Interfaces\Http\RoutesInterface;
use Framework\Http\Route\Middleware;

class Route extends Router implements RoutesInterface
{
    /**
     * GET Route
     * @param string $uri
     * @param \Closure|array $action
     * @return self
     */

    public function get(string $uri, \Closure|array $action): self
    {
        return $this->match('GET|HEAD', $uri, $action);
    }

    /**
     * POST Route
     * @param string $uri
     * @param \Closure|array $action
     * @return self
     */

    public function post(string $uri, \Closure|array $action): self
    {
        return $this->match('POST', $uri, $action);
    }


    /**
     * PUT Route
     * @param string $uri
     * @param \Closure|array $action
     * @return self
     */

    public function put(string $uri, \Closure|array $action): self
    {
        return $this->match('PUT', $uri, $action);
    }

    /**
     * UPDATE Route
     * @param string $uri
     * @param \Closure|array $action
     * @return self
     */

    public function update(string $uri, \Closure|array $action): self
    {
        return $this->match('PATCH', $uri, $action);
    }

    /**
     * DELETE Route
     * @param string $uri
     * @param \Closure|array $action
     * @return self
     */

    public function delete(string $uri, \Closure|array $action): self
    {
        return $this->match('DELETE', $uri, $action);
    }

    /**
     * match Route
     * match more request methods for one route
     * @param string $methods  '|' separator
     * @param string $uri
     * @param \Closure|array $action
     * @return self
     */

    public function match(string $methods, string $uri, \Closure|array $action): self
    {
        // add route
        self::addRoute(explode('|', strtoupper($methods)), $uri, $action);

        // return self
        return $this;
    }

    /**
     * Middleware function
     * @param bool|string|array $validateRules
     * @return self
     */

    public function middleware(bool|string|array $validateRules): self
    {
        // update route middlewares
        $this->middlewares = array_unique([...$this->middlewares,...(array)$validateRules]);

        // return self
        return $this;
    }

    /**
     * Prefix function
     * set group prefix
     * @param string $prefix
     * @return self
     */

    public function prefix(string $prefix): self
    {
        if (empty($prefix)) {
            throw new \Exception('You must enter a prefix for a route/group of routes!', 1);
        }

        // add prefix to prefixs array
        $this->prefixs[] = trim($prefix, '/');

        // make prefix
        $this->prefix = str_replace('//', '/', '/'.$this->groupPrefix.'/'.trim($prefix, '/'));

        return $this;
    }

    /**
     * Group function
     * Group routes with prefix or middlewares
     * @param \Closure $action
     * @return void
     */

    public function group(\Closure $action)
    {
        // keep track of first prefix/middlwares of main group
        $prefix = $this->groupPrefix;
        $middlewares = $this->groupMiddlwares;

        // set prefix to group prefix
        $this->groupPrefix = $this->prefix;

        // merge middlwares met group middlewares
        $this->groupMiddlwares = [...$this->middlewares,...$this->groupMiddlwares];

        // check of er wel group middlware / prefix is
        if (empty($this->groupMiddlwares) && empty($this->groupPrefix)) {
            throw new \Exception("Je moet een middleware of route prefix gebruiken om de group functie te kunnen gebruiken", 1);
        }

        // call callback function
        // and clone current class to $groupedRoutes
        call_user_func($action, $routeGroup = clone $this);

        // merge nieuwe routes met huidige routes
        $this->routes = $routeGroup->routes;

        // reset waardes voor andere routes
        $this->groupMiddlwares = [];

        // set prefix to first prefix from main group
        $this->groupPrefix = $prefix;
        $this->prefix = $prefix;

        // set middlewares to first middlewares from main group
        $this->middlewares = $middlewares;
    }

    /**
     * pattern function
     * Give a route a name
     * @param string $routeName
     * @return self
     */

    public function pattern(array $pattern): self
    {
        // check if pattern is empty
        if (empty($pattern)) {
            throw new \Exception('You must enter an pattern!', 1);
        }
        // check if there exist routes
        if (!$this->routes) {
            throw new \Exception('There are no routes to apply the name to!', 1);
        }

        // voeg naam toe aan route
        $this->routes[array_key_last($this->routes)]['patterns'] = $pattern;
        // return self
        return $this;
    }

    /**
     * pattern function
     * Give a route an name to access based on on the given name
     * @param string $pattern
     * @return self
     */

    public function name(string $routeName): self
    {
        // check if routena is empty
        if (empty($routeName)) {
            throw new \Exception('You must enter an routeName!', 1);
        }
        // check if there exist routes
        if (is_null($this->routes)) {
            throw new \Exception('There are no routes to apply the name to!', 1);
        }

        // get last route key that is inserted
        $routeKey = array_key_last($this->routes);
        // voeg naam toe aan route
        $this->routes[$routeKey]['name'] = $routeName;
        // voeg toe aan namedRoutes
        $this->namedRoutes[$routeName] = $this->routes;
        // return self
        return $this;
    }

    /**
     * getRouteByName function
     * get a routeURL by given name
     * @param string $routeName
     * @return boolean|string
     */

    public function getRouteByName(string $routeName, array $params = []): string
    {
        // krijg alle routes
        $route = array_filter($this->routes, function ($route) use ($routeName) {
            return $route['name'] === $routeName;
        });

        // return route uri
        return !isset(array_values($route)[0]) ? '' : $this->replaceDynamicRoute(
            array_values($route)[0]['uri'],
            $params
        );
    }

    /**
     * getCurrentRoute function
     * get current route
     * @return array
     */

    public function getCurrentRoute(): array
    {
        return $this->currentRoute ?: ['name' => '','route' => '', 'method' => '', 'urls' => [],'middlewares' => '','patterns' => []];
    }
}
