<?php

namespace Framework\Collection;

trait CollectionHelpers
{
	/**
	 * This method will loop over all the items without touching the items
	 *
	 * @param callable $callback
	 * @return Collection
	 */
	public function each(callable $callback): Collection
	{
		// loop over all the items
		foreach ($this->toArray() as $key => $item) {
			// check if need to break loop
			if ($callback($item, $key) === false) {
				break;
			}
		}

		return $this;
	}

	/**
	 * This will loop over all the items
	 *
	 * @param callable $callback
	 * @return Collection
	 */
	public function map(callable $callback): Collection
	{
		// get all keys
		$keys = array_keys($this->toArray());

		// map over the items and keep the keys
		$items = array_map($callback, $this->toArray(), $keys);

		// combine new array with old keys
		return new static(array_combine($keys, $items));
	}

	/**
	 * This method will filter out items
	 *
	 * @param callable|null $callback
	 * @return Collection
	 */
	public function filter(?callable $callback = null): Collection
	{
		// check if there was a callable set
		if (is_null($callback)) {
			return new static(array_filter($this->toArray()));
		}

		// execute callable
		return new static(array_filter($this->toArray(), $callback, ARRAY_FILTER_USE_BOTH));
	}

	/**
	 * This method will flatten a array to 1 depth
	 *
	 * @return Collection
	 */
	public function flatten(): Collection
	{
		return new static(flattenArray($this->toArray()));
	}

	/**
	 * This method will get the first item from the collection
	 *
	 * @param callable|null $callback
	 * @return mixed
	 */
	public function first(?callable $callback = null): mixed
	{
		// when there is no data found
		if (empty($this->toArray())) {
			return false;
		}

		// loop over the collection values
		foreach ($this->toArray() as $key => $item) {
			// when there is no callback function
			if (is_null($callback)) {
				return $item;
			}

			// when callback returns true
			if ($callback($item, $key)) {
				return $item;
			}
		}

		// when there was no data found (when the closure failed)
		return false;
	}

	/**
	 * This method will get the last method from the collection
	 *
	 * @param callable|null $callback
	 * @return mixed
	 */
	public function last(?callable $callback = null): mixed
	{
		return static::make(
			array_reverse($this->toArray(), true)
		)->first($callback);
	}

	/**
	 * This method will slice collection
	 *
	 * @param integer $offset
	 * @param integer $length
	 * @return static
	 */
	public function slice(int $offset, int $length): static
	{
		return new static(array_slice($this->toArray(), $offset, $length, true));
	}

	/**
	 * This method will get all keys from the collection
	 *
	 * @param mixed $keys
	 * @return Collection
	 */
	public function keys(mixed $keys = null): Collection
	{
		return new static(
			$keys ? array_keys($this->toArray(), $keys) : array_keys($this->toArray())
		);
	}

	/**
	 * This method will combine keys with values
	 *
	 * @param Collection|array $values
	 * @return Collection
	 */
	public function combine(Collection|array $values): Collection
	{
		return new static(
			array_combine(
				$this->toArray(),
				$values instanceof Collection ? $values->toArray() : $values,
			)
		);
	}
}
