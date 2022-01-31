<?php

namespace Framework\Http\Request;

final class RequestHeader extends GetAble
{
	/**
	 * @var array<string, string>
	 */
	protected array $headers = [];

	/**
	 * @param  ServerHeader $server
	 */
	public function __construct(
		private ServerHeader $server
	) {
		// init getable
		parent::__construct('headers');

		// merge all headers
		$this->headers = function_exists('getallheaders') ? getallheaders() : $server->all();
	}

	public function __get(string $name): mixed
	{
		return $this->get($name);
	}

	public function __set($name, $value): void
	{
		$this->headers[$name] = $value;
	}
}
