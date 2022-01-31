<?php

use Curl\Curl;
use Framework\Http\Redirect\Redirect;
use Framework\Collection\Collection;
use Framework\Http\Route\Route;
use Framework\Content\Content;
use Framework\Http\Response;
use Framework\Http\Request;
use Framework\Cache\Cache;
use Framework\Content\Seo;
use Framework\Debug\Ray;
use Framework\App;
use Framework\Http\Http;

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
 * @param mixed $value
 * @return mixed
 */
function clearInjections(mixed $value): mixed
{
	// check if is not the right type
	if (!is_array($value) && !is_string($value)) {
		return $value;
	}

	// kijk of input value een array is
	if (is_array($value)) {
		// ga door alle keys/values heen
		return collection($value)->map(function ($item) {
			return clearInjections($item);
		})->toArray();
	}

	return htmlspecialchars($value);
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
		// clear xxs
		echo clearInjections(print_r($value, true));
	}

	echo "</pre>";

	return true;
}

/**
 * @param string|object $class
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
 * @return Request|mixed
 */
function request(string|int|null $find = null)
{
	global $request;

	$request = app('request') ?: app(
		$request instanceof Request ? $request : new Request
	);

	return !is_null($find) ?
		(array_key_exists($find, $request->requestData) ? $request->requestData[$find] : null) :
		$request;
}

/**
 * @param  string|null $baseUrl
 * @return Curl
 */
function http(?string $baseUrl = null): Curl
{
	/** @var Curl */
	return new Http($baseUrl);
}

/**
 * @return Response
 */
function response(): Response
{
	return new Response();
}

/**
 * @param string $path
 * @param int $responseCode
 * @param bool|null $secure
 * @return Redirect
 */
function redirect(?string $path = null, int $responseCode = 302, bool|null $secure = null): Redirect
{
	return new Redirect($path, $responseCode, $secure);
}

/**
 * @param  string|null  $viewPath
 * @param  boolean      $defaultLayout
 * @return Content|null
 */
function content(?string $viewPath = null, string|false $defaultLayout = false): ?Content
{
	global $content;

	return app('content') ?: app(
		$content instanceof Content ? $content : new Content($viewPath, $defaultLayout)
	);
}

/**
 * @return Seo|null
 * @throws ReflectionException
 */
function seo(): ?Seo
{
	global $seo;

	return app('seo') ?: app(
		$seo instanceof Seo ? $seo : new Seo()
	);
}

/**
 * @return Route|null
 * @throws ReflectionException
 */
function route(): ?Route
{
	global $route;

	return app('route') ?: app(
		$route instanceof Route ? $route : new Route()
	);
}

/**
 * @return Cache
 * 
 * @throws ReflectionException
 */
function cache(): Cache
{
	global $cache;

	return app('cache') ?: app(
		$cache instanceof Cache ? $cache : new Cache()
	);
}

/**
 * @param Object|string|null
 * @return Framework\App|object|null
 * @throws ReflectionException
 */
function app(object|string|null $class = null)
{
	global $app;

	// check if app is an instance of app class
	if (!$app instanceof App) {
		$app = new App;
	}

	// check if is object
	if (is_object($class)) {
		// set/get instance
		return $app->setInstance($class)->getInstance(lcfirst(getClassName($class)));
	} // when you want to access an stored class
	elseif (is_string($class)) {
		return $app->getInstance($class);
	}

	return $app;
}

/**
 * @param mixed ...$data
 * @return Ray
 */
function ray(mixed ...$data)
{
	if (!app()->rayIsEnabled()) {
		return new class
		{
			public function __call($name, $arguments)
			{
				return $this;
			}
		};
	}

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

/**
 * This method will create a new collection
 *
 * @param array|object $collection
 * @return Collection
 */
function collection(array|object $collection): Collection
{
	return Collection::make($collection);
}

/**
 * This function flatten an array to one layer
 *
 * @param array $array
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
 * This method will detect if an value is an multidimensional array
 *
 * @param mixed $value
 * @return boolean
 */
function isMultidimensional(mixed $value): bool
{
	return is_array($value) && is_array($value[array_key_first($value)]);
}
