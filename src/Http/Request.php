<?php

namespace Framework\Http;

use Framework\Http\Validate\RequestValidator;
use ReflectionException;

class Request extends RequestValidator
{
	use RequestParser;

	/**
	 * @var array
	 */
	private array $server = [];

	/**
	 * @var object|null
	 */
	private ?object $getData;

	/**
	 * @var object|null
	 */
	private ?object $postData;

	/**
	 * @var object|null
	 */
	private ?object $fileData;

	/**
	 * @var object
	 */
	public object $requestData;


	private string $method;
	private string $schema = '';

	/**
	 * HTTP/1.1|HTTP/1.0|HTTPS/1.1
	 *
	 * @var string
	 */
	private string $protocol = '';
	private string $host = '';

	private string $requestUri = '';
	private string $queryString = '';

	public function __construct()
	{
		// set server information
		$this->server = $_SERVER;

		// set all request(get) data
		$this->getData = (object) clearInjections($_GET);

		// set all request(post) data
		$this->postData = (object) clearInjections(array_merge(
			json_decode(file_get_contents('php://input'), true) ?? [],
			$_POST
		));

		// add all request files(upload)
		$this->fileData = (object) $_FILES;

		// merge all request data
		$this->requestData = (object) array_merge(
			(array) $this->getData,
			(array) $this->postData,
			(array) $this->fileData
		);
	}

	/**
	 * This function will return value of class property is exists
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name): mixed
	{
		return property_exists($this->requestData, $name) ? $this->requestData->{$name} : null;
	}

	/**
	 * This function will return all get data or the value by find key
	 * @param string|int|null $find
	 * @return mixed
	 */

	public function get(string|int|null $find = null): mixed
	{
		// check if find is null
		if (is_null($find)) {
			return $this->getData;
		}

		// return get data
		return property_exists($this->getData, $find) ? $this->getData->{$find} : null;
	}

	/**
	 * This function will return all post data or the value by find key
	 * @param string|int|null $find
	 * @return mixed
	 */
	public function post(string|int|null $find = null): mixed
	{
		// check if find is null
		if (is_null($find)) {
			return $this->postData;
		}

		// return get data based on find value
		return property_exists($this->postData, $find) ? $this->postData->{$find} : null;
	}

	/**
	 * This function will return all request file(s)
	 * @param string|null $find
	 */
	public function file(?string $find = null): mixed
	{
		// check if find is null
		if (is_null($find)) {
			return $this->fileData;
		}

		// return get data based on find value
		return property_exists($this->fileData, $find) ? $this->fileData->{$find} : null;
	}

	/**
	 * This function will return all request information
	 * @return ?object
	 */
	public function all(): ?object
	{
		return $this->requestData;
	}

	/**
	 * This function will check if all keys exists in the current request
	 * @param string $requestKey
	 * @return bool
	 */
	public function exists(string ...$requestKey): bool
	{
		// loop trough all func args
		foreach ($requestKey as $key) {
			// check if key exists
			if (!property_exists($this->requestData, $key)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function csrf(): string
	{
		return $_SESSION['_csrf_token'] ?? $_SESSION['_csrf_token'] = randomString(40);
	}

	/**
	 * @return bool
	 * @throws ReflectionException
	 */
	public function validateCsrf(): bool
	{
		return \request('_token') === $this->csrf();
	}

	/**
	 * This function will return current uri like: /route/to
	 * @return string
	 */
	public function uri(bool $withTrailingSlash = false): string
	{
		// krijg de huidige url zonden get waardes
		return rtrim($this->parseUri(), $withTrailingSlash ? '' : '/') ?: '/';
	}

	/**
	 * This function will return the query string
	 * @return string
	 */
	public function query(): string
	{
		return $this->parseQuery();
	}

	/**
	 * This method will get the path form the current url
	 *
	 * @return string
	 */
	public function host(): string
	{
		return $this->parseHost();
	}

	/**
	 * This function will return current URL with params
	 * @return string
	 */
	public function url(bool $withTrailingSlash = false): string
	{
		// krijg de huidige url zonden get waardes
		return rtrim($this->server['REQUEST_URI'], $withTrailingSlash ? '/' : '') ?: '/';
	}

	/**
	 * This function will return all headers of one header based on the findHeader param
	 * @param string|null $findHeader
	 * @return array|string|null
	 */
	public function headers(?string $findHeader = null): array|string|null
	{
		// keep track of all headers
		$headers = [];

		// If getallheaders() is available, use that
		if (function_exists('getallheaders')) {
			// get all headers
			$headers = getallheaders();

			// getallheaders() can return false if something went wrong
			if ($headers !== false) {
				return $findHeader ? $headers[$findHeader] ?? null : $headers;
			}
		}

		// Method getallheaders() not available or went wrong: manually extract 'm
		foreach ($_SERVER as $name => $value) {
			if ((str_starts_with($name, 'HTTP_')) || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
				$headers[str_replace(
					[' ', 'Http'],
					['-', 'HTTP'],
					ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
				)] = $value;
			}
		}

		// return header(s) based on find header key
		return $findHeader ? (array_key_exists($findHeader, $headers) ? $headers[$findHeader] : null) : $headers;
	}

	/**
	 * This function will return current request type
	 * @return string
	 */
	public function method(): string
	{
		// Take the method as found in $_SERVER
		$method = strtoupper($_SERVER['REQUEST_METHOD']);

		// If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
		// @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
		if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
			// start output buffer
			ob_start();
			// set method to get
			$method = 'GET';
		}

		// If it's a POST request, check for a method override header
		elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(strtoupper($this->headers('X-HTTP-Method-Override') ?? ''), ['PUT', 'DELETE', 'PATCH'])) {
			// check if headers exists
			$method = $this->headers('X-HTTP-Method-Override');
		}

		// return method in strtoupper
		return strtoupper($method);
	}
}
