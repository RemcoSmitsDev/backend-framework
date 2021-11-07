<?php

namespace Framework\Http\Route;

use Framework\Interfaces\Http\RoutesInterface;
use Framework\Http\Route\Middleware;

class Route extends Router implements RoutesInterface
{
    /**
     * GET Route
     * @static
     * @param string $route
     * @param \Closure $callback
     * @return self
     */

    public static function get(string $route, \Closure|array $callback): self
    {
        return self::addRoute('GET', $route, $callback);
    }

    /**
     * POST Route
     * @static
     * @param string $route
     * @param \Closure $callback
     * @return self
     */

    public static function post(string $route, \Closure $callback): self
    {
        return self::addRoute('POST', $route, $callback);
    }


    /**
     * PUT Route
     * @static
     * @param string $route
     * @param \Closure $callback
     * @return self
     */

    public static function put(string $route, \Closure $callback): self
    {
        return self::addRoute('PUT', $route, $callback);
    }

    /**
     * UPDATE Route
     * @static
     * @param string $route
     * @param \Closure $callback
     * @return self
     */

    public static function update(string $route, \Closure $callback): self
    {
        return self::addRoute('PATCH', $route, $callback);
    }

    /**
     * DELETE Route
     * @static
     * @param string $route
     * @param \Closure $callback
     * @return self
     */

    public static function delete(string $route, \Closure $callback): self
    {
        return self::addRoute('DELETE', $route, $callback);
    }

    /**
     * Middleware function
     * @static
     * @param bool|string|array $validateRules
     * @return self
     */

    public static function middleware(bool|string|array $validateRules): self
    {
        // update route middlewares
        self::$middlewares = array_unique([...self::$middlewares,...(array)$validateRules]);
        // return new self
        return new self();
    }

    /**
     * Prefix function
     * set group prefix
     * @static
     * @param string $prefix
     * @return self
     */

    public static function prefix(string $prefix): self
    {
        if (empty($prefix)) {
            throw new \Exception('You must enter a prefix for a route/group of routes!', 1);
        }
        self::$prefix = str_replace('//', '/', '/'.self::$groupPrefix.'/'.trim($prefix, '/'));
        return new self();
    }

    /**
     * Group function
     * Group routes with prefix or middlewares
     * @param \Closure $callback
     * @return void
     */

    public function group(\Closure $callback)
    {
        // keep track of first prefix/middlwares of main group
        $prefix = self::$groupPrefix;
        $middlewares = self::$groupMiddlwares;

        // set prefix to group prefix
        self::$groupPrefix = self::$prefix;

        // merge middlwares met group middlewares
        self::$groupMiddlwares = [...self::$middlewares,...self::$groupMiddlwares];

        if (empty(self::$groupMiddlwares) && empty(self::$groupPrefix)) {
            throw new \Exception("Je moet een middleware of route prefix gebruiken om de group functie te kunnen gebruiken", 1);
        }

        // make new instance of Route class
        $routeGroup = clone new self();
        $routeGroup::$groupPrefix = self::$groupPrefix;
        $routeGroup::$groupMiddlwares = self::$groupMiddlwares;

        // call callback function
        // and clone current class to $groupedRoutes
        call_user_func($callback, $routeGroup);

        // merge nieuwe routes met huidige routes
        self::$routes = array_merge($routeGroup::$routes, self::$routes);

        // reset waardes voor andere routes
        self::$groupMiddlwares = [];
        self::$groupPrefix = '';

        // set prefix to first prefix from main group
        self::$prefix = $prefix;
        // set middlewares to first middlewares from main group
        self::$middlewares = $middlewares;
    }

    /**
     * pattern function
     * Give a route a name
     * @static
     * @param string $routeName
     * @return self
     */

    public static function pattern(array $pattern): self
    {
        // check if pattern is empty
        if (empty($pattern)) {
            throw new \Exception('You must enter an pattern!', 1);
        }
        // check if there exist routes
        if (!self::$routes) {
            throw new \Exception('There are no routes to apply the name to!', 1);
        }
        // get last route key that is inserted
        $routeKey = array_key_last(self::$routes[self::$requestMethod]);
        // voeg naam toe aan route
        self::$routes[self::$requestMethod][$routeKey]['patterns'] = $pattern;
        // return self
        return new self();
    }

    /**
     * pattern function
     * Give a route an name to access based on on the given name
     * @static
     * @param string $pattern
     * @return self
     */

    public static function name(string $routeName): self
    {
        // check if routena is empty
        if (empty($routeName)) {
            throw new \Exception('You must enter an routeName!', 1);
        }
        // check if there exist routes
        if (!self::$routes) {
            throw new \Exception('There are no routes to apply the name to!', 1);
        }
        // get last route key that is inserted
        $routeKey = array_key_last(self::$routes[self::$requestMethod]);
        // voeg naam toe aan route
        self::$routes[self::$requestMethod][$routeKey]['name'] = $routeName;
        // voeg toe aan namedRoutes
        self::$namedRoutes[$routeName] = self::$routes[self::$requestMethod][$routeKey];
        // return self
        return new self();
    }

    /**
     * Urls function
     * give a dynamic route specific urls that are only valid urls for that route
     * @static
     * @param array $validURLs
     * @return self
     */

    public static function urls(array $validURLs): self
    {
        // check if urls is empty
        if (empty($validURLs)) {
            throw new \Exception('You must enter a array of valid urls!', 1);
        }
        // check if there exist routes
        if (!self::$routes) {
            throw new \Exception('There are no routes to apply the name to!', 1);
        }
        // get all routes by requestMethod
        $routes = self::getRoutes(self::$requestMethod);
        // put urls to last route
        $routes[array_key_last($routes)]['urls'] = $validURLs;
        // return self
        return new self();
    }


    /**
     * getRouteByName function
     * get a routeURL by given name
     * @static
     * @param string $routeName
     * @return boolean|string
     */

    public static function getRouteByName(string $routeName, array $params = [])
    {
        // krijg alle routes
        $routes = self::getRoutes('GET');

        // maak een column van alle namen
        $routerNames = array_column($routes, 'name');

        // als er geen naam is gevonden dan wordt er een lege url terug gestuurd
        if (!in_array($routeName, $routerNames)) {
            return false;
        }

        // get route by index
        $route = $routes[array_search($routeName, $routerNames)];

        // return route with replaced dynamic params
        return self::replaceDynamicRoute($route['route'], $params);
    }

    /**
     * getCurrentRoute function
     * get current route
     * @return Object
     */

    public static function getCurrentRoute(): Object
    {
        return (object)(self::$currentRoute ?: ['name' => '','route' => '', 'method' => '', 'urls' => [],'middlewares' => '','patterns' => []]);
    }
}
