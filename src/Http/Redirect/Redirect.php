<?php

namespace Framework\Http\Redirect;

class Redirect
{
	/**
	 * @param string $path
	 * @param int $responseCode
	 * @param bool|null $secure
	 */
	public function __construct(string $path, int $responseCode = 302, bool $secure = null)
	{
		// match http/https
		$path = preg_replace('/(http|https):\/\//i', '', $path);

		// check if server protocol must be secure(https) or http
		$secure = ($secure || preg_match('/https/i', $_SERVER['SERVER_PROTOCOL'] ?? '')) ? 'https://' : 'http://';

		// check if path has host inside path
		preg_match('/^(www\.)*([A-z\.0-9]+)/', $path, $match);

		// set host
		$host = empty($match) ? HTTP_HOST : $match[0];

		// replace host from path
		$uri = preg_replace('/^(www\.)*([A-z\.0-9]+)/', '', $path);

		// redirect
		header('Location: ' . $secure . $host . $uri, true, $responseCode);

		// exit
		exit;
	}
}
