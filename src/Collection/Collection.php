<?php

namespace Framework\Collection;

use ArrayIterator;
use Closure;
use Countable;
use Exception;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;

class Collection implements IteratorAggregate, Countable, JsonSerializable, Stringable
{
    use CollectionHelpers;

    /**
     * This will keep track of all items of the collection.
     *
     * @var array<int|string, mixed>
     */
    protected array $items = [];

    /**
     * @param array<int|string, mixed>|Traversable|Collection|Closure $collection
     */
    public function __construct(array|Traversable|Collection|Closure $collection = [])
    {
        $this->items = $this->getCollection($collection);
    }

    /**
     * This method wil create an instance of a new collection.
     *
     * @param array<int|string, mixed>|Traversable|Collection $collection
     *
     * @return Collection
     */
    public static function make(array|Traversable|Collection $collection): Collection
    {
        return new Collection($collection);
    }

    /**
     * This method will return the right format for the collection to apply to.
     *
     * @param array<int|string, mixed>|Traversable|Collection|Closure $collection
     *
     * @return array<int|string, mixed>
     */
    private function getCollection(array|Traversable|Collection|Closure $collection): array
    {
        if ($collection instanceof Collection) {
            return $collection->toArray();
        } elseif (is_array($collection)) {
            return $collection;
        } elseif ($collection instanceof Traversable) {
            return iterator_to_array($collection);
        } elseif ($collection instanceof Closure) {
            return iterator_to_array($collection());
        }

        throw new Exception('You must pass in a valid collection type! (array|Traversable|Collection|Closure)');
    }

    /**
     * This method will return array of items from the collection.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * This method will return array of items from the collection.
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->toArray();
    }

    /**
     * This method will format collection to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * This method will get all items from the collection to a string with a separator.
     *
     * @return string
     */
    public function toString(string $separator = ', '): string
    {
        return implode($separator, $this->all());
    }

    /**
     * this will allow collection to be formatted to json.
     *
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }

    /**
     * This method will return all results when you use the is without getting is to an array first.
     *
     * @return ArrayIterator<int|string, mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }

    /**
     * This method will count the total length of the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }
}
