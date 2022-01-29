<?php

namespace Framework\Http\Request;


trait GetAble
{
	/**
	 * This will keep track of the data key(propert name)
	 * 
	 * @var string
	 */
	private string $_name = 'data';

	/**
	 * This method will get all headers
	 *
	 * @return array
	 */
	public function all(int|false $case = false): array
	{
		return $case !== false ? array_change_key_case($this->{$this->getName()}, $case) : $this->headers;
	}

	/**
	 * This method will get/find a single header
	 *
	 * @param  string      $name
	 * @return string|null
	 */
	public function get(string $name): ?string
	{
		return $this->has($name) ? $this->all(CASE_LOWER)[strtolower($name)] : null;
	}

	/**
	 * This will check if there was an header with the given name
	 *
	 * @param  string  $name
	 * @return boolean
	 */
	public function has(string $name): bool
	{
		return array_key_exists(strtolower($name), $this->all(CASE_LOWER));
	}

	/**
	 * This will set the propery name where to find from
	 *
	 * @param  string $name
	 * @return void
	 */
	protected function setName(string $name)
	{
		$this->_name = $name;
	}

	/**
	 * This method will get the 
	 *
	 * @return string
	 */
	private function getName(): string
	{
		return $this->_name;
	}
}
