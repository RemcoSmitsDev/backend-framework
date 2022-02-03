<?php

namespace Framework\Http\Route;

use Framework\Container\Container;
use Framework\Http\Request;
use Exception;
use Closure;

class Router
{
    /**
     * Keeps track of all routes
     * @var array $routes
     */
    protected array $routes = [];

    /**
     * Keeps track of all names routes
     * @var array $namedRoutes
     */
    protected array $namedRoutes = [];

    /**
     * Keeps track of current prefix
     * @var string $prefix
     */
    protected string $prefix = '';

    /**
     * Keeps track of current groupprefix
     * @var string $groupPrefix
     */
    protected string $groupPrefix = '';

    /**
     * Keeps track of current middlewares
     * @var array $middlewares
     */
    protected array $middlewares = [];

    /**
     * Keeps track of curren group middlewares
     * @var array $groupMiddlewares
     */
    protected array $groupMiddlewares = [];

    /**
     * Keeps track of current Route
     * @var ?array $currentRoute
     */
    protected ?array $currentRoute = null;

    /**
     * Default dynamic route pattern
     * @var string $routeParamPattern
     */
    private string $routeParamPattern = '\{[A-Za-z_]+[0-9]*\}';

    /**
     * Keeps track of request class
     * @var Request $request
     */
    protected Request $request;

    /**
     * @var callable
     */
    private $onMiddlewareFailCallback;

    /**
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $this->request = request();
    }

    /**
     * Handles the action(function/class method)
     * @param array $route
     * @param array $data
     * @return void
     * @throws Exception
     */
    protected function handleRouteAction(array $route, array $data = []): void
    {
        // set action function
        $action = $route['action'];

        // set current route
        $this->currentRoute = $route;

        // when array [Test::class,'index']
        if (is_array($action)) {
            // check if information is correct
            if (!isset($action[0], $action[1]) || !is_string($action[0]) || !is_string($action[1])) {
                throw new Exception("Your array must have as first item an class and as seconde item function name!");
            }
            // is no class was found throw error
            if (!class_exists($action[0])) {
                throw new Exception("Class `{$action[0]}` couldn't be found!");
            }

            // make instance of class
            $class = new $action[0]();

            // method
            $method = $action[1];

            // check if function exists
            if (!method_exists($class, $method)) {
                throw new Exception("Method `{$method}` couldn't be found inside the `" . $class::class . "` class!");
            }

            // call method with dependencies
            $class->{$method}(...Container::handleClassMethod($class::class, $method, $data));

            // stop function
            return;
        }

        // check if action is and closure
        if (!$action instanceof Closure) {
            throw new Exception("Action must be a instnaceof \Closure or a callable([Test::class,'index'])!");
        }

        // call function
        call_user_func($action, ...Container::handleClosure($action, $data));
    }

    /**
     * Get all routes bij a requestMethod
     * @return null|array
     */
    public function getRoutes(): ?array
    {
        return $this->routes ?? null;
    }

    /**
     * add route to routes array based on requestMethod
     * @param array $methods
     * @param string $uri
     * @param Closure|array $action
     * @return Router
     */
    protected function addRoute(array $methods, string $uri, Closure|array $action): self
    {
        // replace alle dubbele slashes
        $uri = preg_replace("/\/+/", "/", '/' . $this->prefix . '/' . $uri);

        // kijk of er nog wat overblijf als je laatste slash verwijder
        // anders is '/' -> ''
        $uri = rtrim($uri, '/') ?: $uri;

        // voeg de route toe aan bij het request type
        $this->routes[] = [
            'uri' => $uri,
            'isDynamic' => preg_match('/' . $this->routeParamPattern . '/', $uri),
            'methods' => $methods,
            'name' => '',
            'action' => $action,
            'patterns' => [],
            'middlewares' => [
                ...$this->middlewares,
            ]
        ];

        // reset alle middlewares/prefix
        $this->middlewares = $this->groupMiddlewares;
        $this->prefix = $this->groupPrefix;

        return $this;
    }

    /**
     * replace all dynamic routing params to regex
     * @param string $uri
     * @param array $route
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
        return "/^" . preg_replace('/' . $this->routeParamPattern . '/', "([^\/]+)", str_replace('/', '\/', $uri)) . "(?!.)/";
    }

    /**
     * kijk of er de route dynamic params heeft
     * als de route dynamic routes heeft en deze matched mat de current url
     * return dan de dynamic params values met de naam als key
     * @param array $route
     * @return boolean|array
     */
    private function validateDynamicUri(array $route): bool|array
    {
        // get regex pattern by routeURL
        $regexString = $this->replaceRouteURLPatterns($route['uri'], $route);

        // check if there is an match
        if (!preg_match($regexString, $this->request->uri())) {
            return false;
        }

        // keep track of all dynamic route params
        $data = [];

        // explode route url into parts to get values from dynamic route
        $explodeCurrentURL = explode('/', trim($this->request->uri(), '/'));
        $explodeRouteURL = explode('/', trim($route['uri'], '/'));

        // loop trough all url parts
        foreach ($explodeRouteURL as $key => $part) {
            // check if dynamic parameter was found
            if (preg_match('/' . $this->routeParamPattern . '/', $part)) {
                // add data to globals
                $data[preg_replace('/\{|\}|^[0-9]+/', '', $part)] = clearInjections($explodeCurrentURL[$key]);
            }
        }

        return $data;
    }

    /**
     * This function replaces dynamic routes and checks if all the dynamic parts are fulfilled
     * @param string $route
     * @param array $params
     * @param array $wrongParams
     * @return string
     * @throws Exception
     */
    protected function replaceDynamicRoute(string $route, array $params = [], array $wrongParams = []): string
    {
        // check if there are dynamic params in route url
        if (!preg_match('/' . $this->routeParamPattern . '/', $route)) {
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
            $route = preg_replace_callback("/\{{$key}\}/", function ($string) use ($value, $key, &$params) {
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
     * @return Router
     */
    public function onMiddlewareFail(callable $callback): self
    {
        $this->onMiddlewareFailCallback = $callback;

        return $this;
    }

    /**
     * This method will handle when an middleware fails
     * @param array $failedRoute
     * @throws \ReflectionException
     */
    private function handleOnMiddlewareFail(array $failedRoute): void
    {
        // check if there is a middle fallback callback
        if (isset($this->onMiddlewareFailCallback)) {
            // return response code
            response()->code(403);

            // call on middleware fail callback
            call_user_func($this->onMiddlewareFailCallback, ...Container::handleClosure(
                $this->onMiddlewareFailCallback,
                ['route' => $failedRoute]
            ));

            // stop other actions
            response()->exit();
        } else {
            // get output cache
            ob_get_clean();

            // send forbidden response code
            response()->code(403)->view('responseView')->exit();
        }
    }

    /**
     * get route by current request url
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
        $uri = $this->request->uri();

        // loop trough all routes
        foreach ($this->getRoutes() as $route) {
            // check if request method is in array of methods
            if (!in_array($this->request->method(), $route['methods'])) {
                continue;
            }

            // when is not dynamic and found match
            if (!$route['isDynamic']) {
                // when uri is not the same as current uri go to the next in the array
                if ($route['uri'] !== $uri) {
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
                $this->handleRouteAction($route);

                // break foreach
                break;
            } else {
                // check if a dynamic route match the current url
                if (($data = $this->validateDynamicUri($route)) === false) {
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
                $this->handleRouteAction($route, $data);

                // break foreach
                break;
            }
        }

        // where there is no route
        if (!$this->currentRoute) {
            // get output cache
            ob_get_clean();

            response()->code(404)->view('responseView')->exit();
        }
    }
}
