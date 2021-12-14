<?php

use Framework\Http\Redirect\Redirect;
use Framework\Http\Route\Route;
use Framework\Content\Content;
use Framework\Http\Response;
use Framework\Http\Request;
use Framework\Cache\Cache;
use Framework\Content\Seo;
use Framework\Http\Http;
use Framework\Debug\Ray;
use Framework\App;

/**
 * @param string $input
 * @return string
 */
function stripAccents(string $input): string
{
    //Unwanted array
    $unwantedArray = ['Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'ü' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'];

    //Return the input
    return strtr($input, $unwantedArray);
}

/**
 * @param int $length
 * @return string
 */
function randomString(int $length = 15): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

/**
 * @param string|array $value
 * @return string|array
 */
function clearInjections(string|array $value): string|array
{
    // kijk of input value een array is
    if (is_array($value)) {
        // ga door alle keys/values heen
        return array_map(function ($value) {
            return clearInjections($value);
        }, $value);
    }

    return htmlspecialchars(trim($value));
}

/**
 * @param mixed ...$values
 * @return bool
 */
function dd(mixed ...$values): bool
{
    // check if is not development
    if (defined('IS_DEVELOPMENT_MODE') && !IS_DEVELOPMENT_MODE) {
        return false;
    }

    echo "<pre style='width:auto;overflow:auto;'>";
    foreach ($values as $value) {
        print_r($value);
    }
    echo "</pre>";

    return true;
}

/**
 * @param class-string<object>|object $class
 * @return string
 * @throws ReflectionException
 */
function getClassName(object|string $class): string
{
    $class = new \ReflectionClass($class);

    return $class->getShortName();
}

/**
 * @param string|int|null $find
 * @return mixed
 * @throws ReflectionException
 */
function request(string|int $find = null): mixed
{
    global $request;

    $request = app('request') ?? app(
        $request instanceof Request ? $request : new Request()
    );

    return !is_null($find) ?
        (property_exists($request->requestData, $find) ? $request->requestData->{$find} : null) :
        $request;
}

/**
 * @return Http
 */
function http(): Http
{
    return new Http();
}

/**
 * @return Response
 */
function response(): Response
{
    global $response;

    return $response instanceof Response ? $response : new Response();
}

/**
 * @param string $path
 * @param int $responseCode
 * @param bool|null $secure
 * @return Redirect
 */
function redirect(string $path, int $responseCode = 302, bool $secure = null): Redirect
{
    return new Redirect($path, $responseCode, $secure);
}

/**
 * @return object|null
 * @throws ReflectionException
 */
function content(): object|null
{
    global $content;

    return app('content') ?: app(
        $content instanceof Content ? $content : new Content()
    );
}

/**
 * @return object|null
 * @throws ReflectionException
 */
function seo(): object|null
{
    global $seo;

    return app('seo') ?: app(
        $seo instanceof Seo ? $seo : new Seo()
    );
}

/**
 * @return object|null
 * @throws ReflectionException
 */
function route(): object|null
{
    global $route;

    return app('route') ?: app(
        $route instanceof Route ? $route : new Route()
    );
}

/**
 * @return object|null
 * @throws ReflectionException
 */
function cache(): object|null
{
    global $cache;

    return app('cache') ?: app(
        $cache instanceof Cache ? $cache : new Cache()
    );
}

/**
 * @param Object|string|null $class
 * @return object|null
 * @throws ReflectionException
 */
function app(object|string $class = null): object|null
{
    global $app;

    // check if app is an instance of app class
    if (!$app instanceof App) {
        $app = new App;
    }

    // check if is object
    if (is_object($class)) {
        return $app->instance($class)->{lcfirst(getClassName($class))};
    } // when you want to access an stored class
    elseif (is_string($class)) {
        return $app->{$class} ?? null;
    }

    return $app;
}

/**
 * @param mixed ...$data
 * @return Ray
 */
function ray(mixed ...$data)
{
    return new class($data, debug_backtrace()) extends Ray
    {
        public function __construct(private mixed $_data, array $trace)
        {
            // call parent constructor
            parent::__construct();

            // set backtrace
            $this->backtrace = $trace;

            // check if there exists an global instance
            if (app('ray')) {
                // keep track of measure info
                $this->measure = app('ray')->measure;
            }
        }

        public function __destruct()
        {
            if ($this->_data) {
                $this->data($this->_data)->send();
            } else {
                $this->send();
            }

            // update global instance to keep track of information that need to be keeped
            app()->ray = $this;
        }
    };
}
