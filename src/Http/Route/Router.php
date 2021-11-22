<?php

namespace Framework\Http\Route;

use Framework\DependencyInjectionContainer\DependencyInjectionContainer;
use Framework\Http\Request;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];

    protected string $prefix = '';
    protected array $middlewares = [];

    protected string $group = '';
    protected array $groupMiddlwares = [];
    protected string $groupPrefix = '';

    protected array $currentRoute = [];

    // helping global patterns
    private string $routeParamPattern = '\{[A-Za-z_]+[0-9]*\}';

    protected Request $request;


    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * handleRouteAction function
     * Handles the action(function/ class function)
     * @param $action
     */

    protected function handleRouteAction(array $route, array $data = [])
    {
        // default value
        $dependencies = [];

        // set action function
        $action = $route['action'];

        // set current route
        $this->currentRoute = $route;

        // when array [Test::class,'index']
        if (is_array($action)) {
            // check if information is correct
            if (!isset($action[0], $action[1]) || !is_string($action[0]) || !is_string($action[1])) {
                throw new \Exception("Your array must have as first item an class and as seconde item function name", 1);
            }
            // is no class was found throw error
            if (!class_exists($action[0])) {
                throw new \Exception("Class couldn't be found", 1);
            }
            // make instance of class
            $class = new $action[0]();

            // check if function exists
            if (!method_exists($class, $action[1])) {
                throw new \Exception("Method couldn't be found", 1);
            }

            // get method from action array
            $method = $action[1];

            // call function
            $dependencies = DependencyInjectionContainer::handleClassMethod($action[0], $action[1], $data);

            // call method with dependencies
            $class->$method(...$dependencies);
            // stop function
            return;
        }

        // check if action is and closure
        if (!$action instanceof \Closure) {
            throw new \Exception("Action must be an instanceof and \Closure or and [Test::class,'index']", 1);
        }

        // krijg alle dependencies van de closure function
        $dependencies = DependencyInjectionContainer::handleClosure($action, $data);

        // call function
        call_user_func($action, ...$dependencies);
    }

    /**
     * getRoutes function
     * Get all routes bij a requestMethod
     * @return null|array
     */

    public function getRoutes(): ?array
    {
        return $this->routes ?? null;
    }

    /**
     * addRoute function
     * add route to routes array based on requestMethod
     * @param array $methods
     * @param string $uri
     * @param \Closure|array $action
     */

    protected function addRoute(array $methods, string $uri, \Closure|array $action): self
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
        $this->middlewares = $this->groupMiddlwares;
        $this->prefix = $this->groupPrefix;

        return $this;
    }

    /**
     * replaceRouteURLPatterns function
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
        return "/^" . preg_replace('/' . $this->routeParamPattern . '/', "([^\/]*+)", str_replace('/', '\/', $uri)) . "(?!.)/";
    }

    /**
     * validateDynamicUri function
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

    protected function replaceDynamicRoute(string $route, array $params = [], array $wrongParams = [])
    {
        // check if there are dynamic params in route url
        if (!preg_match('/' . $this->routeParamPattern . '/', $route)) {
            return $route;
        }

        // check if params are empty
        // there must be params bc route has dynamic params
        if (empty($params)) {
            throw new \Exception("You must pass in params based on the dynamic route! \n\n Route: {$route}, Wrong params: " . json_encode($wrongParams), 1);
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
     * init function
     * get route by current request url
     * @return boolean
     */

    public function init()
    {
        // krijg current request url
        $uri = $this->request->uri();

        // check if there are no routes yet
        if (is_null($this->getRoutes())) {
            // geen routes
            return false;
        }

        // clean ouput buffer for HEAD request
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_start();
        }

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
                if (!Middleware::validate($route['middlewares'])) {
                    // TODO: hier moet gehandeld worden dat je geen rechten hebt iets van een 500 page/code ofzo
                    return false;
                }

                // call needed function
                $this->handleRouteAction($route);

                // set currentRoute
                $this->currentRoute = $route;

                // break foreach
                break;
            } else {
                // check if de route has middlewares and check if there are passed
                if (!Middleware::validate($route['middlewares'])) {
                    continue;
                }

                // check if a dynamic route match the current url
                if (($data = $this->validateDynamicUri($route)) === false) {
                    continue;
                }

                // call needed function
                $this->handleRouteAction($route, $data);

                // set currentRoute
                $this->currentRoute = $route;

                // break foreach
                break;
            }
        }

        // clean ouput buffer for HEAD request
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_end_clean();
        }
    }
}