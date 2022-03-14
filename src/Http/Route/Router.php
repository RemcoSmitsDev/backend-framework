<?php

declare(strict_types=1);

namespace Framework\Http\Route;

use Closure;
use Exception;
use Framework\Container\DependencyInjector;
use Framework\Model\BaseModel;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class Router
{
    /**
     * @var array
     */
    protected array $routes = [];

    /**
     * @var array
     */
    protected array $namedRoutes = [];

    /**
     * @var string
     */
    protected string $prefix = '';

    /**
     * @var string
     */
    protected string $groupPrefix = '';

    /**
     * @var array
     */
    protected array $middlewares = [];

    /**
     * @var array
     */
    protected array $groupMiddlewares = [];

    /**
     * @var ?array
     */
    protected ?array $currentRoute = null;

    /**
     * @var string
     */
    const DYNAMIC_ROUTE_PATTERN = '\{[A-Za-z_]+[0-9]*(\:[A-Za-z_]+)*\}';

    /**
     * @var callable
     */
    private $onMiddlewareFailCallback;

    /**
     * Handles the action(function/class method).
     *
     * @param array $route
     * @param array $dynamicData
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function handleRouteAction(array $route, array $dynamicData = []): mixed
    {
        $this->currentRoute = $route;

        $resolver = DependencyInjector::resolve(
            target: is_array($route['action']) ? $route['action'][0] : $route['action'],
            method: is_array($route['action']) ? $route['action'][1] : null
        )->with($dynamicData);

        return $resolver->with(
            $this->handleRouteModelBinding($route, $resolver->getParameters(), $dynamicData)
        )->getContent();
    }

    /**
     * @param array<int,mixed>    $parameters
     * @param array<string,mixed> $dynamicData
     */
    private function handleRouteModelBinding(array $route, array $parameters, array $dynamicData): array
    {
        // loop through all dynamic params with values
        foreach ($dynamicData as $key => $value) {
            // explode dynamic param
            $parts = explode(':', $key, 2);

            // loop through all the parameters
            $parameters = collection($parameters)->map(function ($parameter) use ($parts, $value, $route, $dynamicData) {
                // when is not a model
                if (!$parameter instanceof BaseModel) {
                    return $parameter;
                }

                // get classname
                $classname = getClassName($parameter);

                // when is no the model that belongs to the dynamic param
                if (strtolower($classname) !== strtolower($parts[0])) {
                    return;
                }

                // get data by dynamic route
                $bindingData = $parameter->routeModelBinding($parts[1] ?? $parameter->getPrimaryKey(), $value, $dynamicData);

                // abort when there is no data found
                if ($bindingData === null && $route['onRouteModelBindingFail'] !== null) {
                    ($route['onRouteModelBindingFail'])();
                }

                // append data to model
                $parameter->setOriginal($bindingData->getOriginal());

                return $parameter;
            })->all();
        }

        return $parameters;
    }

    /**
     * Get all routes bij a requestMethod.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * add route to routes array based on requestMethod.
     *
     * @param array         $methods
     * @param string        $uri
     * @param Closure|array $action
     *
     * @return Router
     */
    protected function addRoute(array $methods, string $uri, Closure|array $action): self
    {
        // replace alle dubbele slashes
        $uri = preg_replace("/\/+/", '/', '/' . $this->prefix . '/' . $uri);

        // kijk of er nog wat overblijf als je laatste slash verwijder
        // anders is '/' -> ''
        $uri = rtrim($uri, '/') ?: $uri;

        // when GET is inside methods and HEAD is not
        if (in_array('GET', $methods) && !in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }

        // voeg de route toe aan bij het request type
        $this->routes[] = [
            'uri'         => $uri,
            'isDynamic'   => preg_match('/' . self::DYNAMIC_ROUTE_PATTERN . '/', $uri),
            'methods'     => $methods,
            'name'        => '',
            'action'      => $action,
            'patterns'    => [],
            'middlewares' => [
                ...$this->middlewares,
            ],
            'onRouteModelBindingFail' => function () {
                abort(404);
            },
        ];

        // reset alle middlewares/prefix
        $this->middlewares = $this->groupMiddlewares;
        $this->prefix = $this->groupPrefix;

        return $this;
    }

    /**
     * replace all dynamic routing params to regex.
     *
     * @param string $uri
     * @param array  $route
     *
     * @return string
     */
    private function replaceRouteURLPatterns(string $uri, array $route): string
    {
        // check if there where patterns set
        if (!empty($route['patterns'])) {
            // replace dynamic patterns
            foreach ($route['patterns'] as $key => $regexPattern) {
                // replace regex pattern
                $uri = str_replace("{{$key}}", "({$regexPattern})", $uri);
            }
        }

        // make regex string and replace other patterns
        return '/^' . preg_replace('/' . self::DYNAMIC_ROUTE_PATTERN . '/', "([^\/]+)", str_replace('/', '\/', $uri)) . '(?!.)/';
    }

    /**
     * kijk of er de route dynamic params heeft
     * als de route dynamic routes heeft en deze matched mat de current url
     * return dan de dynamic params values met de naam als key.
     *
     * @param array $route
     *
     * @return bool|array
     */
    private function validateDynamicUri(array $route): bool|array
    {
        // get regex pattern by routeURL
        $regexString = $this->replaceRouteURLPatterns($route['uri'], $route);

        // check if there is an match
        if (!preg_match($regexString, request()->uri())) {
            return false;
        }

        // keep track of all dynamic route params
        $data = [];

        // explode route url into parts to get values from dynamic route
        $explodeCurrentURL = explode('/', trim(request()->uri(), '/'));
        $explodeRouteURL = explode('/', trim($route['uri'], '/'));

        // loop trough all url parts
        foreach ($explodeRouteURL as $key => $part) {
            // check if dynamic parameter was found
            if (preg_match('/' . self::DYNAMIC_ROUTE_PATTERN . '/', $part)) {
                // add data to globals
                $data[preg_replace('/\{|\}|^[0-9]+/', '', $part)] = clearInjections($explodeCurrentURL[$key]);
            }
        }

        return $data;
    }

    /**
     * This function replaces dynamic routes and checks if all the dynamic parts are fulfilled.
     *
     * @param string $route
     * @param array  $params
     * @param array  $wrongParams
     *
     * @throws Exception
     *
     * @return string
     */
    protected function replaceDynamicRoute(string $route, array $params = [], array $wrongParams = []): string
    {
        // check if there are dynamic params in route url
        if (!preg_match('/' . self::DYNAMIC_ROUTE_PATTERN . '/', $route)) {
            return $route;
        }

        // check if params are empty
        // there must be params bc route has dynamic params
        if (empty($params)) {
            throw new Exception("You must pass in params based on the dynamic route! \n\n Route: {$route}, Wrong params: " . json_encode($wrongParams) . '!');
        }

        // loop trough all params and replace params
        foreach ($params as $key => $value) {
            // replace param and remove param from array when is found and replaced
            $route = preg_replace_callback("/\{{$key}(\:[A-z_]+)\}/", function () use ($value, $key, &$params) {
                // remove param from array
                unset($params[$key]);
                // return value
                return $value;
            }, $route);
        }

        // return replaced route
        // return failedParams($params)
        return $this->replaceDynamicRoute($route, [], $params);
    }

    /**
     * @param callable $callback
     *
     * @return Router
     */
    public function onMiddlewareFail(callable $callback): self
    {
        $this->onMiddlewareFailCallback = $callback;

        return $this;
    }

    /**
     * This method will handle when an middleware fails.
     *
     * @param array $failedRoute
     *
     * @throws \ReflectionException
     */
    private function handleOnMiddlewareFail(array $failedRoute): void
    {
        // check if there is a middle fallback callback
        if (isset($this->onMiddlewareFailCallback)) {
            // return response code
            response()->code(403);

            // call function with dependencies injection
            DependencyInjector::resolve($this->onMiddlewareFailCallback)->with(['route' => $failedRoute])->getContent();

            // stop other actions
            response()->exit();
        } else {
            // send forbidden response code
            abort(403);
        }
    }

    /**
     * get route by current request url.
     *
     * @throws Exception
     */
    public function init()
    {
        // check if there are no routes yet
        if (is_null($this->getRoutes())) {
            // geen routes
            return false;
        }

        // krijg current request url
        $uri = request()->uri();

        // loop trough all routes
        foreach ($this->getRoutes() as $route) {
            // check if request method is in array of methods
            if (!in_array(request()->method(), $route['methods'])) {
                continue;
            }

            // when is not dynamic and found match
            if (!$route['isDynamic'] && $route['uri'] !== $uri) {
                // when uri is not the same as current uri go to the next in the array
                continue;
            }

            // check if a dynamic route match the current url
            if ($route['isDynamic'] && ($data = $this->validateDynamicUri($route)) === false) {
                continue;
            }

            // check if middleware is valid
            if (!Middleware::validate($route, $route['middlewares'])) {
                // handle response when the middleware was not success
                $this->handleOnMiddlewareFail($route);
            }

            // set currentRoute
            $this->currentRoute = $route;

            // call needed function
            $this->handleRouteAction($route, $data ?? []);

            // break foreach
            break;
        }

        // where there is no route
        if (!$this->currentRoute) {
            abort(404);
        }
    }
}
