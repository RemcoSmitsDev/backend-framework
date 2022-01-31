<?php

namespace Framework\Http\Request;

use IteratorAggregate;
use JsonSerializable;
use ArrayIterator;
use ArrayAccess;
use Countable;

class GetAble implements Countable, JsonSerializable, ArrayAccess, IteratorAggregate
{
	/**
	 * $_name keeps track of the property name where the value is stored
	 *
	 * @param  string $name
	 */
	protected function __construct(
		private string $name
	) {
	}

	/**
	 * This method will get all the values
	 *
	 * @return array
	 */
	public function all(int|false $case = false): array
	{
		return $case !== false ? array_change_key_case($this->{$this->getName()}, $case) : $this->{$this->getName()};
	}

	/**
	 * This method will get single value
	 *
	 * @param  mixed       $name
	 * @param  string|null $default
	 * @return string|null
	 */
	public function get(mixed $name, ?string $default = null): ?string
	{
		return $this->has($name) ? $this->all(CASE_LOWER)[strtolower($name)] : $default;
	}

	/**
	 * This method will check if there exist an value by the name(key)
	 *
	 * @param  mixed  $name
	 * @return boolean
	 */
	public function has(mixed $name): bool
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
		$this->name = $name;
	}

	/**
	 * This method will get the 
	 *
	 * @return string
	 */
	protected function getName(): string
	{
		return $this->name;
	}

	/**
	 * This will count the total items
	 *
	 * @return integer
	 */
	public function count(): int
	{
		return count($this->all());
	}

	/**
	 * Convert to json string
	 *
	 * @return string
	 */
	public function jsonSerialize(): string
	{
		return json_encode($this->all());
	}

	/**
	 * This method will check if value exists by offset
	 *
	 * @param  mixed   $offset
	 * @return boolean
	 */
	public function offsetExists(mixed $offset): bool
	{
		return $this->has($offset);
	}

	/**
	 * This method will get value by offset
	 *
	 * @param  mixed $offset
	 * @return mixed
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return $this->get($offset);
	}

	/**
	 * This set a value by an offset
	 *
	 * @param  mixed $offset
	 * @param  mixed $value
	 * @return void
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->{$this->getName()}[$offset] = $value;
	}

	/**
	 * This will unset a value by offset
	 *
	 * @param  mixed $offset
	 * @return void
	 */
	public function offsetUnset(mixed $offset): void
	{
		unset($this->{$this->getName()}[$offset]);
	}

	/**
	 * This method will allow you to use the class instance inside a foreach
	 *
	 * @return ArrayIterator
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->all());
	}
}
