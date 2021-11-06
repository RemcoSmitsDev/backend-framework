<?php

namespace Framework\Http\Route;

use Framework\Interfaces\Http\RoutesInterface;
use Framework\DependencyInjectionContainer\DependencyInjectionContainer;
use Framework\Http\Route\Middleware;
use Framework\Http\Request;

class Route implements RoutesInterface
{
    private static array $routes = [];
    private static array $namedRoutes = [];

    private static string $requestType = '';
    private static string $prefix = '';
    private static array $middlewares = [];

    private static string $group = '';
    private static array $groupMiddlwares = [];
    private static string $groupPrefix = '';

    private static array $currentRoute = [];


    // helping global patterns
    private static string $routeParamPattern = '\{[A-Za-z_]+\}';


    /**
     * GET Route
     * @static
     * @param string $route
     * @param \Closure $callback
     * @return self
     */

    public static function get(string $route, \Closure|array $callback): self
    {
        return self::addRoute('get', $route, $callback);
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
        return self::addRoute('post', $route, $callback);
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
        return self::addRoute('put', $route, $callback);
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
        return self::addRoute('patch', $route, $callback);
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
        return self::addRoute('delete', $route, $callback);
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
        // merge middlwares met group middlewares
        self::$groupMiddlwares = [...self::$middlewares,...self::$groupMiddlwares];
        self::$groupPrefix = self::$prefix;
        self::$middlewares = [];

        if (empty(self::$groupMiddlwares) && empty(self::$prefix)) {
            throw new \Exception("Je moet een middleware of route prefix gebruiken om de group functie te kunnen gebruiken", 1);
        }

        call_user_func($callback, $groupedRoutes = clone new self());

        self::$middlewares = self::$groupMiddlwares;

        // merge nieuwe routes met huidige routes
        self::$routes = array_merge($groupedRoutes::$routes, self::$routes);

        // reset waardes voor andere routes
        self::$groupMiddlwares = [];
        self::$prefix = '';
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
        $routeKey = array_key_last(self::$routes[self::$requestType]);
        // voeg naam toe aan route
        self::$routes[self::$requestType][$routeKey]['patterns'] = $pattern;
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
        $routeKey = array_key_last(self::$routes[self::$requestType]);
        // voeg naam toe aan route
        self::$routes[self::$requestType][$routeKey]['name'] = $routeName;
        // voeg toe aan namedRoutes
        self::$namedRoutes[$routeName] = self::$routes[self::$requestType][$routeKey];
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
        if (empty($validURLs)) {
            throw new \Exception('You must enter a array of valid urls!', 1);
            return new self();
        }
        if (!self::$routes) {
            throw new \Exception('There are no routes to apply the name to!', 1);
            return new self();
        }
        if (!is_array($validURLs)) {
            $validURLs = [$validURLs];
        }
        $routes = self::getRoutes(self::$requestType);
        $routes[array_key_last($routes)]['urls'] = $validURLs;
        return new self();
    }

    /**
     * handleRouteCallback function
     * Handles the callback(function/ class function)
     * @static
     * @param $callback
     */

    private static function handleRouteCallback(array $route, array $data = [])
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
     * Get all routes bij a requestType
     * @static
     * @param string $requestType
     * @return null|array
     */

    public static function getRoutes(string $requestType = 'get'): ?array
    {
        return self::$routes[strtolower($requestType)] ?? null;
    }

    /**
     * addRoute function
     * add route to routes array based on requestType
     * @param string $requestType
     * @param string $route
     * @param \Closure|array $callback
     */

    private static function addRoute(string $requestType, string $route, \Closure|array $callback): self
    {
        // replace alle dubbele slashes
        $route = preg_replace("/\/+/", "/", '/'.self::$prefix.'/'.$route);

        // kijk of er nog wat overblijf als je laatste slash verwijder
        // anders is '/' -> ''
        $route = rtrim($route, '/') ?: $route;

        // voeg de route toe aan bij het request type
        self::$routes[self::$requestType = $requestType][] = [
          'route' => $route,
          'method' => $requestType,
          'name' => '',
          'urls' => [],
          'callback' => $callback,
          'patterns' => [],
          'middlewares' => [
            ...self::$middlewares,
            ...self::$groupMiddlwares
          ]
        ];

        // reset alle middlewares
        self::$middlewares = [];

        return new self();
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
     * checkParam function
     * kijk of er de route dynamic params heeft
     * als de route dynamic routes heeft en deze matched mat de current url
     * return dan de dynamic params values met de naam als key
     * @static
     * @param string $routeURL
     * @param array $route
     * @return boolean|array
     */

    private static function checkParam(string $routeURL, array $route): bool|array
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
            $explodeCurrentURL = explode('/', request()->URL());
            $explodeRouteURL = explode('/', trim($routeURL, '/'));

            // loop trough all url parts
            foreach ($explodeRouteURL as $key => $part) {
                // check if dynamic parameter was found
                if (preg_match('/'.self::$routeParamPattern.'/', $part)) {
                    // add data to globals
                    $data[preg_replace('/\{|\}|^[0-9]+/', '', $part)] = ($explodeCurrentURL[$key]);
                    $GLOBALS[preg_replace('/\{|\}|^[0-9]+/', '', $part)] = ($explodeCurrentURL[$key]);
                }
            }

            return $data;
        }
        return false;
    }

    private static function replaceDynamicRoute(string $route, array $params = [])
    {
        if (strpos($route, '{') === false && strpos($route, '}') == false) {
            return $route;
        }

        if (empty($params)) {
            throw new \Exception("Je hebt niet alle dynamische params ingevuld", 1);
            return $route;
        }

        foreach ($params as $key => $value) {
            $route = str_replace("{{$key}}", $value, $route);
        }

        return self::replaceDynamicRoute($route, $params);
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
        $routes = self::getRoutes('get');

        // maak een column van alle namen
        $routerNames = array_column($routes, 'name');

        // als er geen naam is gevonden dan wordt er een lege url terug gestuurd
        if (!in_array($routeName, $routerNames)) {
            return false;
        }

        $route = $routes[array_search($routeName, $routerNames)];

        return self::replaceDynamicRoute($route['route'], $params);
    }

    /**
     * getCurrentRoute function
     * get current route
     * @return Object
     */

    public static function getCurrentRoute(): Object
    {
        return (object)(self::$currentRoute ?: ['name' => '','route' => '', 'method' => '', 'urls' => [],'middlewares' => '']);
    }

    /**
     * init function
     * get route by current request url
     * @return boolean
     */

    public function init(): bool
    {
        // krijg current request url
        $currentURL = Request::URL();

        // krijg alle routes bij het huidige requestType
        $routes = self::getRoutes(Request::method());

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

            // return zodat
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
            if (($data = self::checkParam($route, $routes[$routeKey])) === false) {
                continue;
            }
            // kijk of de route urls heeft zoja dan kijken of de huidige url bestaat in de array be
            if (empty($routes[$routeKey]['urls']) || (!empty($routes[$routeKey]['urls']) && in_array($currentURL, $routes[$routeKey]['urls']))) {
                // call needed function
                self::handleRouteCallback($routes[$routeKey], $data);
                // return to stop loop and function response
                return true;
            }
        }
        // geen match
        return false;
    }
}
