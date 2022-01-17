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
		// if not empty add space
		$this->before = !empty($this->before) ? $this->before . ' ' : $this->before;
		$this->after = !empty($this->after) ? ' ' . $this->after : $this->after;
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

		return trim("{$this->before}($query){$this->after}");
	}
}
