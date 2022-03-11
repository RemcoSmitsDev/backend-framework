<?php

declare(strict_types=1);

use Curl\Curl;
use Framework\App;
use Framework\Cache\Cache;
use Framework\Collection\Collection;
use Framework\Container\Container;
use Framework\Content\Content;
use Framework\Content\Seo;
use Framework\Debug\Debug;
use Framework\Debug\Ray;
use Framework\Http\Http;
use Framework\Http\Redirect\Redirect;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Route\Route;

/**
 * This function will clear accents.
 *
 * @param string $input
 *
 * @return string
 */
function stripAccents(string $input): string
{
    return strtr(
        $input,
        ['Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'ü' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y']
    );
}

/**
 * This functon will generate a unique/random string.
 *
 * @param int $length
 *
 * @return string
 */
function randomString(int $length = 15): string
{
    $string = '';

    while (($len = strlen($string)) < $length) {
        $size = $length - $len;

        $string .= substr(
            str_replace(
                ['/', '+', '='],
                '',
                base64_encode(random_bytes($size))
            ),
            0,
            $size
        );
    }

    return $string;
}

/**
 * @template TValue
 *
 * This function clears injections (xss)
 *
 * @param TValue $value
 *
 * @return TValue|string|array
 */
function clearInjections(mixed $value)
{
    // check if is not the right type
    if (!is_array($value) && !is_string($value)) {
        return $value;
    }

    // kijk of input value een array is
    if (is_array($value)) {
        // ga door alle keys/values heen
        return collection($value)->map(fn ($item) => clearInjections($item))->toArray();
    }

    return htmlspecialchars($value);
}

/**
 * This function will show the data in a nice formatted dump.
 *
 * @param mixed ...$values
 *
 * @return void
 */
function dd(mixed ...$values)
{
    // check if is not development
    if (!defined('IS_DEVELOPMENT_MODE') || !IS_DEVELOPMENT_MODE) {
        return;
    }

    // append to dd
    Debug::add('dumps', $values);

    // echo dump
    echo "<pre style='width:auto;overflow:auto;'>".collection($values)->map(fn ($value) => clearInjections(print_r($value, true)))->toString('<br>').'</pre>';
}

/**
 * This function will get the short name of a class(without namespace).
 *
 * @param object|class-string<object> $class
 *
 * @throws ReflectionException
 *
 * @return string
 */
function getClassName(object|string $class): string
{
    return (new \ReflectionClass($class))->getShortName();
}

/**
 * This function will get the request singleton
 * When passing a paramters it will find it inside the request.
 *
 * @param string|null $find
 *
 * @return Request|string|array<int|string, mixed>
 */
function request(string|null $find = null)
{
    global $request;

    /** @var Request $request */
    $request = app(Request::class) ?: app(
        $request instanceof Request ? $request : new Request()
    );

    return !is_null($find) ?
        (array_key_exists($find, $request->all()) ? $request->all()[$find] : null) :
        $request;
}

/**
 * This function will return a new instance of Curl class.
 *
 * @param string|null $baseUrl
 *
 * @return Curl
 */
function http(?string $baseUrl = null): Curl
{
    /** @var Curl */
    return new Http($baseUrl);
}

/**
 * This function will return new instance of Response.
 *
 * @return Response
 */
function response(): Response
{
    return new Response();
}

/**
 * @param string
 *
 * @return never
 */
function abort(int $code = 404): never
{
    ob_get_clean();

    if (str_contains(request()->headers('Accept', ''), 'json')) {
        response()->json(['message' => 'Aborted with code: '.$code])->code($code)->exit();
    } else {
        response()->view('responseView')->code($code)->exit();
    }
}

/**
 * This function will redirect you to an url or route.
 *
 * @return Redirect
 */
function redirect(?string $path = null, int $responseCode = 302, bool|null $secure = null): Redirect
{
    return new Redirect($path, $responseCode, $secure);
}

/**
 * This function will get the singleton of Content.
 *
 * @param string|null $viewPath
 * @param string|bool $defaultLayout
 *
 * @return Content
 */
function content(?string $viewPath = null, string|bool $defaultLayout = false): Content
{
    global $content;

    /** @var Content */
    return app(Content::class) ?: app(
        $content instanceof Content ? $content : new Content($viewPath, $defaultLayout)
    );
}

/**
 * This function will get the singleton of Seo.
 *
 * @return Seo
 */
function seo(): Seo
{
    global $seo;

    /** @var Seo */
    return app(Seo::class) ?: app(
        $seo instanceof Seo ? $seo : new Seo()
    );
}

/**
 * This function will get the singleton of Route.
 *
 * @return Route
 */
function route(): Route
{
    global $route;

    /** @var Route */
    return app(Route::class) ?: app(
        $route instanceof Route ? $route : new Route()
    );
}

/**
 * This function will get the singleton of Cache.
 *
 * @return Cache
 */
function cache(): Cache
{
    global $cache;

    /** @var Cache */
    return app(Cache::class) ?: app(
        $cache instanceof Cache ? $cache : new Cache()
    );
}

/**
 * This function will return the app instance
 * If you pass in a parameter it will try to find singleton of the the class name.
 *
 * @param App|object|null $class
 *
 * @return App|object|null
 */
function app(object|string|null $class = null)
{
    if (is_object($class)) {
        return Container::getInstance()->addSingleton($class);
    }

    if (is_string($class)) {
        return Container::getInstance()->getSingleton($class);
    }

    return Container::getInstance()->getSingleton(App::class);
}

/**
 * This function.
 *
 * @param mixed ...$data
 *
 * @return Ray|object
 */
function ray(mixed ...$data)
{
    if (!app()->rayIsEnabled()) {
        return new class() {
            public function __call(string $name, array $arguments): self
            {
                return $this;
            }
        };
    }

    return new class($data, debug_backtrace()) extends Ray {
        public function __construct(private array $_data, array $trace)
        {
            // call parent constructor
            parent::__construct();

            // set backtrace
            $this->backtrace = $trace;

            // check if there exists an global instance
            if (app()->getInstance(Ray::class)) {
                // keep track of measure info
                $this->measure = app()->getInstance(Ray::class)->measure;
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
            app()->setInstance(
                new Ray()
            );
        }
    };
}

/**
 * This function will create a new collection.
 *
 * @param array<int|string, mixed>|Traversable|Collection $collection
 *
 * @return Collection
 */
function collection(array|Traversable|Collection $collection): Collection
{
    return Collection::make($collection);
}

/**
 * This function flatten an array to one layer.
 *
 * @param array $array
 *
 * @return array
 */
function flattenArray(array $array): array
{
    return array_reduce($array, function ($array, $item) {
        // merge flatten array with new value
        return array_merge($array, is_array($item) ? flattenArray($item) : [$item]);
    }, []);
}

/**
 * This function will detect if an value is an multidimensional array.
 *
 * @param mixed $value
 *
 * @return bool
 */
function isMultidimensional(mixed $value): bool
{
    return is_array($value) && is_array($value[array_key_first($value)]);
}

/**
 * This method will get an array without value that has key.
 *
 * @param array  $data
 * @param string ...$without
 *
 * @return array
 */
function arrayWithout(array $data, string ...$without): array
{
    $without = flattenArray($without);

    foreach ($data as $key => $value) {
        if (in_array($key, $without)) {
            unset($data[$key]);
        }
    }

    return $data;
}

/**
 * @param bool  $baseOn
 * @param mixed $value
 * @param mixed $default
 *
 * @return mixed
 */
function tap(bool $basedOn, mixed $value, mixed $default): mixed
{
    return $basedOn ? $value : $default;
}
