<?php

namespace Framework\Http\Request;

final class RequestHeader
{
	use GetAble;

	/**
	 * @var array<string, string>
	 */
	private array $headers = [];

	public function __construct(
		private ServerHeader $server
	) {
		// set name
		$this->setName('headers');

		// merge all headers
		$this->headers = function_exists('getallheaders') ? getallheaders() : $server->all();
	}

	/**
	 * This method will format header(server) name to request header name
	 *
	 * @param  string $name
	 * @return string
	 */
	private function formateHeaderName(string $name): string
	{
		return str_replace(
			[' ', 'Http', '_'],
			['-', 'HTTP', '-'],
			strtolower($name)
		);
	}
}
