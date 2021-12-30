<?php

namespace Framework\Http\Redirect;

final class Redirect
{
	/**
	 * @param string|null $to
	 * @param integer $responseCode
	 * @param boolean|null|null $secure
	 */
	public function __construct(
		private ?string $to,
		private int $responseCode = 302,
		private bool|null $secure = null
	) {
	}

	/**
	 * This method will allow you to easy redirect to routes using the name of the route
	 *
	 * @param string $routeName
	 * @param array $args
	 * 
	 * @return void
	 */
	public function route(string $routeName, array $args = []): void
	{
		$this->to = route()->getRouteByName($routeName, $args);
	}

	/**
	 * This method will format the host for the redirect url
	 *
	 * @return string
	 */
	private function formatHost(): string
	{
		// check if path has host inside path
		preg_match('/^(www\.)*([A-z\.0-9]+)/', $this->to, $match);

		// set host
		return empty($match) ? HTTP_HOST : $match[0];
	}

	/**
	 * This method will get 
	 *
	 * @return string
	 */
	private function getProtocol(): string
	{
		// remove protocol from to url
		$this->to = preg_replace('/^(\w+):\/\//i', '', $this->to);

		// check if secure was set manualy
		if (!is_null($this->secure)) {
			return $this->protocol = $this->secure ? 'https://' : 'http://';
		}

		// get protocol from server protocol
		return preg_match('/^https/i', $_SERVER['SERVER_PROTOCOL'] ?? '') ? 'https://' : 'http://';
	}

	/**
	 * This method will format the uri for the rediret url
	 *
	 * @return string
	 */
	private function formatUri(): string
	{
		// replace host from path
		return $this->uri = preg_replace('/^(www\.)*([A-z\.0-9]+)/', '', $this->to);
	}

	/**
	 * When the class closes it will redirect to formatted url
	 */
	public function __destruct()
	{
		// redirect
		header('Location: ' . $this->getProtocol() . $this->formatHost() . $this->formatUri(), true, $this->responseCode);

		// exit
		exit;
	}
}
