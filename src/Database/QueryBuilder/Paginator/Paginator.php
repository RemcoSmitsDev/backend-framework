<?php

declare(strict_types=1);

namespace Framework\Database\QueryBuilder\Paginator;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Framework\Database\QueryBuilder\QueryBuilder;
use IteratorAggregate;
use JsonSerializable;

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
class Paginator implements IteratorAggregate, JsonSerializable, ArrayAccess
{
    /**
     * Keep track of data paginate data.
     *
     * @var array
     */
    private array $paginate = [
        'first_page'    => 1,
        'prev_page'     => [],
        'current_page'  => null,
        'next_page'     => [],
        'last_page'     => null,
        'total_pages'   => null,
        'total_results' => null,
        'per_page'      => null,
        'results'       => [],
    ];

    /**
     * @param QueryBuilder $query
     * @param int          $totalResults
     * @param int          $perPage
     * @param int          $currentPage
     */
    public function __construct(QueryBuilder $query, int $totalResults, int $perPage, int $currentPage)
    {
        // calc pages
        $this->paginate['total_results'] = intval($totalResults);
        $this->paginate['per_page'] = $perPage > 0 ? $perPage : 1;
        $this->paginate['total_pages'] = $this->calcTotalPages();

        // set current page
        $this->paginate['current_page'] = $currentPage > 0 ? $currentPage : 1;

        // get collection of results
        $this->paginate['results'] = collection($query->offset(($currentPage - 1) * $perPage)->limit($perPage)->all([]));

        // calc prev, next page
        $this->calcPreviousPage();
        $this->calcNextPage();

        // set total pages + last page
        $this->paginate['last_page'] = $this->calcTotalPages();
    }

    /**
     * This method will make a new instance of Paginator class.
     *
     * @param QueryBuilder $query
     * @param int          $totalResults
     * @param int          $perPage
     * @param int          $currentPage
     *
     * @return self
     */
    public static function make(QueryBuilder $query, int $totalResults, int $perPage, int $currentPage): self
    {
        return new self($query, $totalResults, $perPage, $currentPage);
    }

    /**
     * This method will calc total pages.
     *
     * @return int
     */
    private function calcTotalPages(): int
    {
        return ceil($this->paginate['total_results'] / $this->paginate['per_page']);
    }

    /**
     * this method will calc/format previous page.
     *
     * @return void
     */
    private function calcPreviousPage()
    {
        $this->paginate['prev_page'] = [
            'exists' => $this->paginate['current_page'] - 1 > 0,
            'page'   => $this->paginate['current_page'] - 1 > 0 ? $this->paginate['current_page'] - 1 : 1,
        ];
    }

    /**
     * This method will calc/format next page.
     *
     * @return void
     */
    private function calcNextPage()
    {
        $this->paginate['next_page'] = [
            'exists' => $this->paginate['current_page'] + 1 <= $this->paginate['total_pages'],
            'page'   => $this->paginate['current_page'] + 1 > $this->paginate['total_pages'] ? $this->paginate['total_pages'] : $this->paginate['current_page'] + 1,
        ];
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray()['results']->all());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->paginate;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->toArray()[$name];
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->toArray()[$offset];
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->paginate[$offset] = $value;
    }

    /**
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->paginate[$offset]);
        }
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        if (!method_exists($this->toArray()['results'], $name)) {
            throw new BadMethodCallException("There was no method called [{$name}] on the Collection class!");
        }

        return $this->toArray()['results']->{$name}(...$arguments);
    }
}
