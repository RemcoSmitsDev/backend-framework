<?php

namespace Framework\Http;

use Framework\Http\Request\ValidateCsrfToken;
use Framework\Http\Validate\RequestValidator;
use Framework\Http\Request\RequestCookie;
use Framework\Http\Request\RequestHeader;
use Framework\Http\Request\ServerHeader;

final class Request extends RequestValidator
{
	use RequestParser, ValidateCsrfToken;

	/**
	 * @var ServerHeader|null
	 */
	private ?ServerHeader $server;

	/**
	 * @var RequestHeader|null
	 */
	private ?RequestHeader $headers;

	/**
	 * @var RequestCookie|null
	 */
	private ?RequestCookie $cookies;

	/**
	 * @var array|null
	 */
	private ?array $getData;

	/**
	 * @var array|null
	 */
	private ?array $postData;

	/**
	 * @var array|null
	 */
	private ?array $fileData;

	/**
	 * @var array
	 */
	public array $requestData;

	public function __construct()
	{
		// set server information
		$this->server = new ServerHeader;
		$this->headers = new RequestHeader($this->server);
		$this->cookies = new RequestCookie($this->headers);

		// set all request(get) data
		$this->getData = clearInjections($_GET);

		// set all request(post) data
		$this->postData = clearInjections(array_merge(
			json_decode(file_get_contents('php://input'), true) ?? [],
			$_POST
		));

		// add all request files(upload)
		$this->fileData = $_FILES;

		// merge all request data
		$this->requestData = array_merge(
			$this->getData,
			$this->postData,
			$this->fileData
		);
	}

	/**
	 * This function will return value of class property is exists
	 * @param string $offset
	 * @return mixed
	 */
	public function __get(string $offset): mixed
	{
		return array_key_exists($offset, $this->requestData) ? $this->requestData[$offset] : null;
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
		return array_key_exists($find, $this->getData) ? $this->getData[$find] : null;
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
		return array_key_exists($find, $this->postData) ? $this->postData[$find] : null;
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
		return array_key_exists($find, $this->fileData) ? $this->fileData[$find] : null;
	}

	/**
	 * This function will return all request information
	 * @return array|null
	 */
	public function all(): ?array
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
			if (!array_key_exists($key, $this->requestData)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * This function will return current URL with params
	 * @return string
	 */
	public function url(bool $withTrailingSlash = false): string
	{
		// krijg de huidige url zonden get waardes
		return rtrim($this->server->get('REQUEST_URI') ?? '', $withTrailingSlash ? '/' : '') ?: '/';
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
	 * This function will return all headers of one header based on the findHeader param
	 * @param string|null $findHeader
	 * @return RequestHeader|string|null
	 */
	public function headers(?string $findHeader = null, ?string $default = null): RequestHeader|string|null
	{
		return $findHeader ? $this->headers->get($findHeader, $default) : $this->headers;
	}

	/**
	 * This method will find a server header or returns all server headers
	 *
	 * @param  string|null       $findHeader
	 * @return ServerHeader|string|null
	 */
	public function server(?string $findHeader = null, ?string $default = null): ServerHeader|string|null
	{
		return $findHeader ? $this->server->get($findHeader, $default) : $this->server;
	}

	/**
	 * This method will get all cookies from a request or finds a cookie
	 *
	 * @param  string|null       $findCookie
	 * @return RequestCookie|string|null
	 */
	public function cookies(?string $findCookie = null, ?string $default = null): RequestCookie|string|null
	{
		return $findCookie ? $this->cookies->get($findCookie, $default) : $this->cookies;
	}

	/**
	 * This function will return current request type
	 * @return string
	 */
	public function method(): string
	{
		// Take the method as found in $_SERVER
		$method = strtoupper($this->server('REQUEST_METHOD') ?: 'HEAD');

		// If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
		if ($method === 'HEAD') {
			// start output buffer
			ob_start();

			// set method to get
			$method = 'GET';
		}

		// If it's a POST request, check for a method override header
		elseif ($method === 'POST' && in_array(strtoupper($this->headers('X-HTTP-Method-Override') ?: ''), ['PUT', 'DELETE', 'PATCH'])) {
			// check if headers exists
			$method = $this->headers('X-HTTP-Method-Override');
		}

		// return method in strtoupper
		return strtoupper($method);
	}

	/**
	 * This method will generate a new csrf token an stores it inside the session
	 *
	 * @return string
	 */
	public function csrf(): string
	{
		// get/make token
		return $_SESSION['_csrf_token'] = randomString(40);
	}
}
