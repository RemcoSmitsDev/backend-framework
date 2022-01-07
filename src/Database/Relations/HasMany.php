<?php

namespace Framework\Database\Relations;

use Framework\Database\QueryBuilder\QueryBuilder;
use ReflectionClass;

class HasMany
{
	/**
	 * @param string $baseModel
	 * @param string $hasMany
	 * @param string|null $primaryKey
	 * @param string|null $table
	 */
	public function __construct(
		private string $baseModel,
		private string $hasMany,
		private ?string $primaryKey,
		private ?string $table
	) {
	}

	public function make(QueryBuilder $queryBuilder)
	{
		$reflection = new ReflectionClass($this->hasMany);

		$reflection->getProperty('primaryKey')->setAccessible(true);
		$reflection->getProperty('table')->setAccessible(true);

		$class = !$this->primaryKey || !$this->table ? new $this->hasMany : null;

		if (!$this->primaryKey) {
			$this->primaryKey = $this->$reflection->getProperty('primaryKey')->getValue($class);
		}

		if (!$this->table) {
			$this->table = $reflection->getProperty('table')->getValue($class);
		}

		$queryBuilder->logSql()->join(
			formatTableName($this->baseModel),
			$this->table . '.' . substr(formatTableName($this->baseModel), 0, -1) . '_id',
			'=',
			formatTableName($this->baseModel) . '.' . 'id'
		);
	}

	public function getTable()
	{
		return formatTableName($this->baseModel);
	}
}
