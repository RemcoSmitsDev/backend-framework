<?php

namespace Framework\Database\QueryBuilder\SubQuery;

use Framework\Database\QueryBuilder\RawQuery\RawQuery;
use Framework\Database\QueryBuilder\QueryBuilder;
use Stringable;

class SubQuery implements Stringable
{
	/**
	 * @param QueryBuilder $builder
	 * @param QueryBuilder|RawQuery $query
	 * @param string $before
	 * @param string $after
	 * @param boolean $isWhereClause
	 * @param string $boolean
	 */
	public function __construct(
		private QueryBuilder $builder,
		private QueryBuilder|RawQuery $query,
		private string $before = '',
		private string $after = '',
		private bool $isWhereClause = false,
		public string $boolean = 'AND'
	) {
		// set before
		$this->setBefore($this->before);
		$this->setAfter($this->after);
	}

	/**
	 * This method will set the before value for a sub query for example `avg`, `min`, `max`
	 * @param string $before
	 * @return self
	 */
	public function setBefore(string $before): self
	{
		$this->before = !empty($before) ? trim($before) . ' ' : '';

		return $this;
	}

	/**
	 * This method will set the after value for a sub query for example the name
	 * 
	 * @param string $after
	 * @return self
	 */
	public function setAfter(string $after): self
	{
		$this->after = !empty($after) ? ' ' . trim($after) : '';

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBefore(): string
	{
		return $this->before;
	}

	/**
	 * @return string
	 */
	public function getAfter(): string
	{
		return $this->after;
	}

	/**
	 * This method will format the sub query inside the right format
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		if ($this->isWhereClause) {
			$query = $this->builder->formatWhere($this->builder, $this->query->wheres);
		} else {
			$query = $this->builder->selectToSql($this->query)[0];
		}

		return trim($this->getBefore() . "($query)" . $this->getAfter());
	}
}
