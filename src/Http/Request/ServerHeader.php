<?php

namespace Framework\Http\Request;

final class ServerHeader
{
	use GetAble;

	/**
	 * @var array
	 */
	private array $headers = [];

	public function __construct()
	{
		// set name where to find from
		$this->setName('headers');

		// merge all headers
		$this->headers = $_SERVER;
	}

	/**
	 * This method will format the headers to a string
	 *
	 * @return string
	 */
	public function __toString()
	{
		$headers = $this->all();

		array_walk($headers, function (&$value, $key) {
			$value = "{$key}: {$value}";
		});

		return implode(' ', $headers);
	}
}
