<?php

namespace Framework\Http\Request;

final class ServerHeader extends GetAble
{
	/**
	 * @var array
	 */
	protected array $headers = [];

	public function __construct()
	{
		// init getable
		parent::__construct('headers');

		// merge all headers
		$this->headers = $_SERVER;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		// get all headers
		$headers = $this->all();

		// format the header into a valid string
		array_walk($headers, function (&$value, $key) {
			$value = "{$key}: {$value}";
		});

		// make string with ` ` separator
		return implode(' ', $headers);
	}
}
