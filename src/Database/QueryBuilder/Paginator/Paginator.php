<?php

namespace Framework\Database\QueryBuilder\Paginator;

use ArrayIterator;
use Framework\Database\QueryBuilder\QueryBuilder;
use IteratorAggregate;
use JsonSerializable;

class Paginator implements IteratorAggregate, JsonSerializable
{
    /**
     * Keep track of data paginate data.
     *
     * @var array
     */
    private $paginate = [
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
     * @return static
     */
    public static function make(QueryBuilder $query, int $totalResults, int $perPage, int $currentPage): static
    {
        return new static($query, $totalResults, $perPage, $currentPage);
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
        return new ArrayIterator($this->toArray());
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
}
