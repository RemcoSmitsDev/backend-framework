<?php

namespace Framework\Http\Route;

use Framework\DependencyInjectionContainer\DependencyInjectionContainer;

class Router
{
    protected static array $routes = [];
    protected static array $namedRoutes = [];

    protected static array $matchMethods = [];
    protected static string $requestMethod = '';
    protected static string $prefix = '';
    protected static array $middlewares = [];

    protected static string $group = '';
    protected static array $groupMiddlwares = [];
    protected static string $groupPrefix = '';

    protected static array $currentRoute = [];

    // helping global patterns
    private static string $routeParamPattern = '\{[A-Za-z_]+\}';

    /**
     * handleRouteCallback function
     * Handles the callback(function/ class function)
     * @static
     * @param $callback
     */

    protected static function handleRouteCallback(array $route, array $data = [])
    {
        // default value
        $dependencies = [];

        // set callback function
        $callback = $route['callback'];

        // set current route
        self::$currentRoute = $route;

        // when array [Test::class,'index']
        if (is_array($callback)) {
            // check if information is correct
            if (!isset($callback[0],$callback[1]) || !is_string($callback[0]) || !is_string($callback[1])) {
                throw new \Exception("Your array must have as first item an class and as seconde item function name", 1);
            }
            // is no class was found throw error
            if (!class_exists($callback[0])) {
                throw new \Exception("Class couldn't be found", 1);
            }
            // make instance of class
            $class = new $callback[0]();

            // check if function exists
            if (!method_exists($class, $callback[1])) {
                throw new \Exception("Method couldn't be found", 1);
            }

            // get method from callback array
            $method = $callback[1];

            // call function
            $dependencies = DependencyInjectionContainer::handleClassMethod($callback[0], $callback[1], $data);

            // call method with dependencies
            $class->$method(...$dependencies);
            // stop function
            return;
        }

        // check if callback is and closure
        if (!$callback instanceof \Closure) {
            throw new \Exception("Callback must be an instanceof and \Closure or and [Test::class,'index']", 1);
        }

        // krijg alle dependencies van de closure function
        $dependencies = DependencyInjectionContainer::handleClosure($callback, $data);

        // call function
        call_user_func($callback, ...$dependencies);
    }

    /**
     * getRoutes function
     * Get all routes bij a requestMethod
     * @static
     * @param string $requestMethod
     * @return null|array
     */

    public static function getRoutes(string $requestMethod = '*'): ?array
    {
        // check if all routes need get returned
        if ($requestMethod === '*') {
            return self::$routes;
        }
        // return routes based on requestMethod
        return self::$routes[strtoupper($requestMethod)] ?? null;
    }

    /**
     * addRoute function
     * add route to routes array based on requestMethod
     * @param string $requestMethod
     * @param string $route
     * @param \Closure|array $callback
     */

    protected static function addRoute(string $requestMethod, string $route, \Closure|array $callback): self
    {
        // replace alle dubbele slashes
        $route = preg_replace("/\/+/", "/", '/'.self::$prefix.'/'.$route);

        // reset matchMethods(for match method)
        self::$matchMethods = [];

        // kijk of er nog wat overblijf als je laatste slash verwijder
        // anders is '/' -> ''
        $route = rtrim($route, '/') ?: $route;

        // voeg de route toe aan bij het request type
        self::$routes[self::$requestMethod = $requestMethod][] = [
          'route' => $route,
          'method' => $requestMethod,
          'name' => '',
          'urls' => [],
          'callback' => $callback,
          'patterns' => [],
          'middlewares' => [
            ...self::$middlewares,
            // ...self::$groupMiddlwares
          ]
        ];

        // reset alle middlewares/prefix
        self::$middlewares = self::$groupMiddlwares;
        self::$prefix = self::$groupPrefix;

        return new static();
    }

    /**
     * replaceRouteURLPatterns function
     * replace all dynamic routing params to regex
     * @static
     * @param string $routeURL
     * @param array $route
     * @return string
     */

    private static function replaceRouteURLPatterns(string $routeURL, array $route): string
    {
        // check if there where patterns set
        if (!empty($route['patterns'])) {
            // replace dynamic patterns
            foreach ($route['patterns'] as $key => $regexPattern) {
                // replace regex pattern
                $routeURL = str_replace("{{$key}}", "({$regexPattern})", $routeURL);
            }
        }

        // make regex string and replace other patterns
        return "/^" . preg_replace('/'.self::$routeParamPattern.'/', "([^\/]*+)", str_replace('/', '\/', $routeURL)) . "(?!.)/";
    }

    /**
     * checkDynamicParams function
     * kijk of er de route dynamic params heeft
     * als de route dynamic routes heeft en deze matched mat de current url
     * return dan de dynamic params values met de naam als key
     * @static
     * @param string $routeURL
     * @param array $route
     * @return boolean|array
     */

    private static function checkDynamicParams(string $routeURL, array $route): bool|array
    {
        // get regex pattern by routeURL
        $regexString = self::replaceRouteURLPatterns($routeURL, $route);

        // match regex string met current url
        if (preg_match($regexString, request()->URL())) {
            // match all dynamic routes
            preg_match_all('/'.self::$routeParamPattern.'/', $routeURL, $matches, PREG_OFFSET_CAPTURE);

            // check if there where dynamic params found
            if (!isset($matches[0][0][0])) {
                return false;
            }

            // keep track of all dynamic route params
            $data = [];

            // explode route url into parts to get values from dynamic route
            $explodeCurrentURL = explode('/', trim(request()->URL(), '/'));
            $explodeRouteURL = explode('/', trim($routeURL, '/'));

            // loop trough all url parts
            foreach ($explodeRouteURL as $key => $part) {
                // check if dynamic parameter was found
                if (preg_match('/'.self::$routeParamPattern.'/', $part)) {
                    // add data to globals
                    $data[preg_replace('/\{|\}|^[0-9]+/', '', $part)] = clearInjections($explodeCurrentURL[$key]);
                    $GLOBALS[preg_replace('/\{|\}|^[0-9]+/', '', $part)] = clearInjections($explodeCurrentURL[$key]);
                }
            }

            return $data;
        }
        return false;
    }

    protected static function replaceDynamicRoute(string $route, array $params = [], array $wrongParams = [])
    {
        // check if there are dynamic params in route url
        if (!preg_match('/' . self::$routeParamPattern . '/', $route)) {
            return $route;
        }

        // check if params are empty
        // there must be params bc route has dynamic params
        if (empty($params)) {
            throw new \Exception("You must pass in params based on the dynamic route! \n\n Route: {$route}, Wrong params: ".json_encode($wrongParams), 1);
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
        return self::replaceDynamicRoute($route, [], $params);
    }


    /**
     * init function
     * get route by current request url
     * @return boolean
     */

    public function init()
    {
        // krijg current request url
        $currentURL = request()->URL();

        // krijg alle routes bij het huidige requestType
        $routes = self::getRoutes(request()->method());

        // check if there are no routes yet
        if (is_null($routes)) {
            // geen routes
            return false;
        }

        // krijg alle route urls van alle routes bij het huidige request type
        $routeCol = array_column($routes, 'route');

        // check if route is in routes
        // if the route is found get then the key
        if (($routeKey = array_search($currentURL, $routeCol)) !== false) {

            // kijk of er middlewares zijn en kijk of deze allemaal gepassed zijn
            if (!Middleware::validate($routes[$routeKey]['middlewares'])) {
                return false;
            }

            // call needed function
            self::handleRouteCallback($routes[$routeKey]);

            // clean ouput buffer for HEAD request
            if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
                ob_end_clean();
            }

            // stop function
            return true;
        }

        // if not exact in array search for dynamic routes
        // loop trough all routes
        foreach ($routeCol as $routeKey => $route) {
            // check if de route has middlewares and check if there are passed
            if (!Middleware::validate($routes[$routeKey]['middlewares'])) {
                continue;
            }
            // check if a dynamic route match the current url
            if (($data = self::checkDynamicParams($route, $routes[$routeKey])) === false) {
                continue;
            }
            // kijk of de route urls heeft zoja dan kijken of de huidige url bestaat in de array be
            if (empty($routes[$routeKey]['urls']) || (!empty($routes[$routeKey]['urls']) && in_array($currentURL, $routes[$routeKey]['urls']))) {
                // call needed function
                self::handleRouteCallback($routes[$routeKey], $data);

                // return to stop loop and function response
                break;
            }
        }
        // clean ouput buffer for HEAD request
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_end_clean();
        }
    }
}
