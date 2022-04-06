<?php

declare(strict_types=1);

namespace Framework\Collection;

use Framework\Support\Helpers\Arr;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
trait CollectionHelpers
{
    /**
     * This method will loop over all the items without touching the items.
     *
     * @param callable $callback
     *
     * @return Collection
     */
    public function each(callable $callback): Collection
    {
        // loop over all the items
        foreach ($this as $key => $item) {
            // check if need to break loop
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * This will loop over all the items.
     *
     * @param callable $callback
     *
     * @return Collection
     */
    public function map(callable $callback): Collection
    {
        // return new Collection(function () use ($callback) {
        //     foreach ($this as $key => $value) {
        //         yield $key => $callback($value, $key);
        //     }
        // });
        // get all keys
        $keys = array_keys($this->all());

        // map over the items and keep the keys
        $items = array_map($callback, $this->all(), $keys);

        // combine new array with old keys
        return new Collection(array_combine($keys, $items));
    }

    /**
     * This method will filter out items.
     *
     * @param callable|null $callback
     *
     * @return Collection
     */
    public function filter(?callable $callback = null): Collection
    {
        // check if there was a callable set
        if (is_null($callback)) {
            return Collection::make(array_filter($this->all()));
        }

        // return new Collection(function () use ($callback) {
        //     foreach ($this as $key => $value) {
        //         if ($callback($value, $key)) {
        //             yield $key => $value;
        //         }
        //     }
        // });
        return new Collection(
            array_filter(
                $this->all(),
                $callback,
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    /**
     * This method will flatten a array to 1 depth.
     *
     * @return Collection
     */
    public function flatten(): Collection
    {
        return new Collection(
            Arr::flatten(
                $this->all()
            )
        );
    }

    /**
     * This method will get the first item from the collection.
     *
     * @param callable|null $callback
     *
     * @return mixed
     */
    public function first(?callable $callback = null): mixed
    {
        // when there is no data found
        if (empty($this->all())) {
            return false;
        }

        // loop over the collection values
        return Arr::first($this->all(), $callback);
    }

    /**
     * This method will get the last method from the collection.
     *
     * @param callable|null $callback
     *
     * @return mixed
     */
    public function last(?callable $callback = null): mixed
    {
        return Arr::last($this->all(), $callback);
    }

    /**
     * This method will slice collection.
     *
     * @param int $offset
     * @param int $length
     *
     * @return Collection
     */
    public function slice(int $offset, int $length): Collection
    {
        return new Collection(
            array_slice(
                $this->all(),
                $offset,
                $length,
                true
            )
        );
    }

    /**
     * This method will get all keys from the collection.
     *
     * @param mixed $keys
     *
     * @return Collection
     */
    public function keys(mixed $keys = null): Collection
    {
        return new Collection(
            $keys ? array_keys($this->all(), $keys) : array_keys($this->all())
        );
    }

    /**
     * @param string $name
     *
     * @return Collection
     */
    public function column(string $column): Collection
    {
        return new Collection(
            array_column(
                $this->all(),
                $column
            )
        );
    }

    /**
     * This method will combine keys with values.
     *
     * @param array|Collection $keys
     *
     * @return Collection
     */
    public function combine(array|Collection $keys): Collection
    {
        return new Collection(
            array_combine(
                $keys instanceof Collection ? array_values($keys->all()) : $keys,
                $this->all(),
            )
        );
    }

    /**
     * @return Collection
     */
    public function unique(): Collection
    {
        return new Collection(array_unique($this->all()));
    }
}
