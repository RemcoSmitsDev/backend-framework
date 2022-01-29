<?php

namespace Framework\Http\Route;

use Framework\Interfaces\Http\RoutesInterface;
use Exception;
use Closure;

class Route extends Router implements RoutesInterface
{
    /**
     * @var array|string[]
     */
    private array $allowedMethods = [
        'get',
        'post',
        'put',
        'patch',
        'delete'
    ];

    /**
     * Call possible request route methods
     * @param string $name
     * @param array $arguments
     * @return self
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        if (!in_array($name, $this->allowedMethods)) {
            throw new Exception('The method: "' . $name . '" is not an valid method!');
        }

        return $this->match(strtoupper($name), ...$arguments);
    }

    /**
     * match Route
     * match more request methods for one route
     * @param string $methods  '|' separator
     * @param string $uri
     * @param Closure|array $action
     * @return self
     */
    public function match(string $methods, string $uri, Closure|array $action): self
    {
        // add route
        return $this->addRoute(explode('|', strtoupper($methods)), $uri, $action);
    }

    /**
     * Middleware function
     * @param bool|string|array $validateRules
     * @return Route
     */
    public function middleware(...$validateRules): Route
    {
        // dd($validateRules);
        // update route middlewares
        $this->middlewares = array_unique([
            ...$this->middlewares,
            ...$validateRules
        ]);

        // return self
        return $this;
    }

    /**
     * Prefix function
     * set group prefix
     * @param string $prefix
     * @return self
     * @throws Exception
     */
    public function prefix(string $prefix): self
    {
        // check if prefix is empty
        if (empty($prefix)) {
            throw new Exception('You must enter a prefix for a route/group of routes!', 1);
        }

        // make prefix
        $this->prefix = str_replace('//', '/', '/' . $this->groupPrefix . '/' . trim($prefix, '/'));

        return $this;
    }

    /**
     * Group function
     * Group routes with prefix or middlewares
     * @param Closure $action
     * @return void
     * @throws Exception
     */
    public function group(Closure $action): void
    {
        // keep track of first prefix/middlwares of main group
        $prefix = $this->groupPrefix;
        $middlewares = $this->groupMiddlewares;

        // set prefix to group prefix
        $this->groupPrefix = $this->prefix;

        // merge middlewares met group middlewares
        $this->groupMiddlewares = [
            ...$this->middlewares,
            ...$this->groupMiddlewares
        ];

        // check of er wel group middleware / prefix is
        if (empty($this->groupMiddlewares) && empty($this->groupPrefix)) {
            throw new Exception("Je moet een middleware of route prefix gebruiken om de group functie te kunnen gebruiken", 1);
        }

        // call callback function
        // and clone current class to $groupedRoutes
        call_user_func($action, $routeGroup = clone $this);

        // merge nieuwe routes met huidige routes
        $this->routes = $routeGroup->routes;

        // reset waardes voor andere routes
        $this->groupMiddlewares = [];

        // set prefix to first prefix from main group
        $this->groupPrefix = $prefix;
        $this->prefix = $prefix;

        // set middlewares to first middlewares from main group
        $this->middlewares = $middlewares;
    }

    /**
     * pattern function
     * Give a route a name
     * @param array $patterns
     * @return self
     * @throws Exception
     */
    public function pattern(array $patterns): self
    {
        // check if pattern is empty
        if (empty($patterns)) {
            throw new Exception('You must enter an pattern!', 1);
        }

        // check if there exist routes
        if (!$this->routes) {
            throw new Exception('There are no routes to apply the name to!', 1);
        }

        // add patterns to route
        $this->routes[array_key_last($this->routes)]['patterns'] = $patterns;

        // return self
        return $this;
    }

    /**
     * pattern function
     * Give a route an name to access based on the given name
     * @param string $routeName
     * @return self
     * @throws Exception
     */
    public function name(string $routeName): self
    {
        // check if routena is empty
        if (empty($routeName)) {
            throw new Exception('You must enter an routeName!', 1);
        }
        // check if there exist routes
        if (is_null($this->routes)) {
            throw new Exception('There are no routes to apply the name to!', 1);
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
     * @param array $params
     * @return string
     * @throws Exception
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
        return $this->currentRoute ?: ['name' => '', 'route' => '', 'method' => '', 'middlewares' => '', 'patterns' => []];
    }
}
