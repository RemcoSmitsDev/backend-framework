<?php

namespace Framework\Collection;

use IteratorAggregate;
use ArrayIterator;
use Countable;

class Collection implements IteratorAggregate, Countable
{
	use CollectionHelpers;

	/**
	 * This will keep track of source of collection
	 *
	 * @var mixed
	 */
	protected mixed $source;

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
		$this->source = $this->items = $this->getCollection($collection);
	}

	/**
	 * This method wil create an instance of a new collection
	 *
	 * @param array|object $collection
	 * @return self
	 */
	public static function make(array|object $collection): self
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
		}
	}

	/**
	 * This method will return the source of the collection
	 *
	 * @return mixed
	 */
	public function source(): mixed
	{
		return $this->source;
	}

	/**
	 * This method will 
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->items;
	}

	/**
	 * This method will return all results when you use the is without getting is to an array first
	 *
	 * @return ArrayIterator
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * This method will count the total length of the collection
	 *
	 * @return integer
	 */
	public function count(): int
	{
		return count($this->items);
	}
}