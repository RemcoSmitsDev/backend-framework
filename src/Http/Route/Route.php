<?php

namespace Framework\Http\Route;

use Closure;
use Exception;
use Framework\Interfaces\Http\RoutesInterface;

/**
 * @method get(string $uri, Closure|array $action)
 * @method post(string $uri, Closure|array $action)
 * @method put(string $uri, Closure|array $action)
 * @method patch(string $uri, Closure|array $action)
 * @method delete(string $uri, Closure|array $action)
 * @method options(string $uri, Closure|array $action)
 * 
 * @see \Framework\Http\Route\Route
 */

class Route extends Router implements RoutesInterface
{
    /**
     * @var array<int,string>
     */
    private array $allowedMethods = [
        'get',
        'post',
        'put',
        'patch',
        'delete',
        'options'
    ];

    /**
     * Call possible request route methods:
     * `GET`, `post`, `put`, `patch`, `delete` or `options`
     * 
     * @param string $name
     * @param array<int,mixed> $arguments
     * @return self
     * 
     * @throws Exception
     *
     * @return self
     */
    public function __call(string $name, array $arguments): self
    {
        if (!in_array($name, $this->allowedMethods)) {
            throw new Exception('The method: "' . $name . '" is not an valid method!');
        }

        return $this->match(strtoupper($name), ...$arguments);
    }

    /**
     * Match more request methods for one route with `|` separator
     * 
     * @param string $methods
     * @param string $uri
     * @param Closure|array<int,string> $action
     * @return self
     */
    public function match(string $methods, string $uri, Closure|array $action): self
    {
        return $this->addRoute(explode('|', strtoupper($methods)), $uri, $action);
    }

    /**
     * Middleware function.
     *
     * @param bool|string|array $validateRules
     *
     * @return Route
     */
    public function middleware(...$validateRules): Route
    {
        // update route middlewares
        $this->middlewares = array_unique([
            ...$this->middlewares,
            ...$validateRules,
        ]);

        // return self
        return $this;
    }

    /**
     * Give a single route a prefix or grouped routes a prefix
     * 
     * @param string $prefix
     * @return self
     * 
     * @throws Exception
     *
     * @return self
     */
    public function prefix(string $prefix): self
    {
        // check if prefix is empty
        if (empty($prefix)) {
            throw new Exception('You must enter a prefix for a route/group of routes!');
        }

        // make prefix
        $this->prefix = str_replace('//', '/', '/' . $this->groupPrefix . '/' . trim($prefix, '/'));

        return $this;
    }

    /**
     * Group routes to apply a prefix or middleware to all nested routes
     * 
     * @param Closure $action
     * @return void
     * 
     * @throws Exception
     *
     * @return void
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
            ...$this->groupMiddlewares,
        ];

        // check of er wel group middleware / prefix is
        if (empty($this->groupMiddlewares) && empty($this->groupPrefix)) {
            throw new Exception('You must use a prefix or middleware to use `group` method!');
        }

        // call callback function
        // and clone current class to $groupedRoutes
        call_user_func($action, $routeGroup = clone $this);

        // merge nieuwe routes met huidige routes
        $this->routes = $routeGroup->routes;

        // set prefix to first prefix from main group
        $this->groupPrefix = $prefix;
        $this->prefix = $prefix;

        // reset waardes voor andere routes
        $this->groupMiddlewares = [];
        // set middlewares to first middlewares from main group
        $this->middlewares = $middlewares;
    }

    /**
     * You can give a route a name so you can get the route information or navigate to the route.
     * 
     * @param array<string,string> $patterns
     * @return self
     * 
     * @throws Exception
     *
     * @return self
     */
    public function pattern(array $patterns): self
    {
        // check if pattern is empty
        if (empty($patterns)) {
            throw new Exception('You must enter an pattern!');
        }

        // check if there exist routes
        if (!$this->routes) {
            throw new Exception('There are no routes to apply the name to!');
        }

        // add patterns to route
        $this->routes[array_key_last($this->routes)]['patterns'] = $patterns;

        // return self
        return $this;
    }

    /**
     * Give a route an name to access based on the given name
     * 
     * @param string $routeName
     * @return self
     * 
     * @throws Exception
     *
     * @return self
     */
    public function name(string $routeName): self
    {
        // check if routena is empty
        if (empty($routeName)) {
            throw new Exception('You must enter an routeName!');
        }
        // check if there exist routes
        if (is_null($this->routes)) {
            throw new Exception('There are no routes to apply the name to!');
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
     * When the route model binding couldn't found a match
     * It will fire this callback or continues when is null
     * 
     * @param Closure|null $callback
     * @return self
     */
    public function actionOnRouteModelBindingFail(?Closure $callback = null): self
    {
        // get last route key that is inserted
        $routeKey = array_key_last($this->routes);
        // voeg naam toe aan route
        $this->routes[$routeKey]['onRouteModelBindingFail'] = $callback;

        return $this;
    }

    /**
     * Gets a route URI by the given name or and empty string when there was no route found
     * 
     * @param string $routeName
     * @param array<string,string> $params
     * @return string
     * 
     * @throws Exception
     *
     * @return string
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
     * Gets the current route information
     * 
     * @return array<string,mixed>
     */
    public function getCurrentRoute(): array
    {
        return $this->currentRoute ?: ['name' => '', 'route' => '', 'method' => '', 'middlewares' => [], 'patterns' => []];
    }

    /**
     * Returns a boolean if the name belongs to current route
     * 
     * @param string $name
     * @return bool
     */
    public function isCurrentRoute(string $name): bool
    {
        return $this->getCurrentRoute()['name'] === $name;
    }
}
