<?php

namespace Framework\Database\QueryBuilder\SubQuery;

use Framework\Database\QueryBuilder\RawQuery\RawQuery;
use Framework\Database\QueryBuilder\QueryBuilder;
use Stringable;

class SubQuery implements Stringable
{
	public function __construct(
		private QueryBuilder $builder,
		private QueryBuilder|RawQuery $query,
		private string $before = '',
		private string $after = '',
		private bool $isWhereClause = false,
		public string $boolean = 'AND'
	) {
	}

	public function query(): QueryBuilder|RawQuery
	{
		return $this->query;
	}

	public function __toString(): string
	{
		if ($this->isWhereClause) {
			$query = $this->builder->formatWhere($this->builder, $this->query()->wheres);
		} else {
			$query = $this->builder->selectToSql($this->query)[0];
		}

		return ltrim(preg_replace('/\s+/', ' ', "{$this->before} ($query) {$this->after}"));
	}
}
