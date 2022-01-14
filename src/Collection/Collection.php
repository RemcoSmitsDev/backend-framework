<?php

namespace Framework\Collection;

use IteratorAggregate;
use JsonSerializable;
use ArrayIterator;
use Stringable;
use Countable;
use Traversable;

class Collection implements IteratorAggregate, Countable, JsonSerializable, Stringable
{
	use CollectionHelpers;

	/**
	 * This will keep track of all items of the collection
	 *
	 * @var array
	 */
	protected array $items = [];

	/**
	 * @param array|object $collection
	 */
	public function __construct(array|object $collection)
	{
		$this->items = $this->getCollection($collection);
	}

	/**
	 * This method wil create an instance of a new collection
	 *
	 * @param array|object $collection
	 * @return static
	 */
	public static function make(array|object $collection): static
	{
		return new static($collection);
	}

	/**
	 * This method will return the right format for the collection to apply to
	 *
	 * @param [type] $collection
	 * @return array
	 */
	private function getCollection(array|object $collection): array
	{
		if ($collection instanceof Collection) {
			return $collection->toArray();
		} elseif (is_array($collection)) {
			return $collection;
		} elseif (is_object($collection)) {
			return [$collection];
		} elseif ($collection instanceof Traversable) {
			return iterator_to_array($collection);
		}
	}

	/**
	 * This method will return array of items from the collection
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->items;
	}

	/**
	 * This method will format collection to string
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->toString();
	}

	/**
	 * This method will get all items from the collection to a string with a separator
	 * 
	 * @return string
	 */
	public function toString(string $separator = ', '): string
	{
		return implode($separator, $this->toArray());
	}

	/**
	 * this will allow collection to be formatted to json
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	/**
	 * This method will return all results when you use the is without getting is to an array first
	 *
	 * @return ArrayIterator
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->toArray());
	}

	/**
	 * This method will count the total length of the collection
	 *
	 * @return integer
	 */
	public function count(): int
	{
		return count($this->toArray());
	}
}
