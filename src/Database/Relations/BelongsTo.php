<?php

namespace Framework\Database\Relations;

use Framework\Database\QueryBuilder\QueryBuilder;
use ReflectionClass;

class BelongsTo
{
	/**
	 * @param string $baseModel
	 * @param string $belongsTo
	 * @param string|null $primaryKey
	 * @param string|null $table
	 */
	public function __construct(
		private string $baseModel,
		private string $belongsTo,
		private ?string $primaryKey,
		private ?string $table
	) {
	}

	public function make(QueryBuilder $queryBuilder)
	{
		$reflection = new ReflectionClass($this->belongsTo);

		$reflection->getProperty('primaryKey')->setAccessible(true);
		$reflection->getProperty('table')->setAccessible(true);

		$class = !$this->primaryKey || !$this->table ? new $this->belongsTo : null;

		if (!$this->primaryKey) {
			$this->primaryKey = $this->$reflection->getProperty('primaryKey')->getValue($class);
		}

		if (!$this->table) {
			$this->table = $reflection->getProperty('table')->getValue($class);
		}

		$queryBuilder->join($this->table, $this->table . '.' . $this->primaryKey, '=', formatTableName($this->baseModel) . '.' . substr($this->table, 0, -1) . '_id');
	}

	public function getTable()
	{
		return formatTableName($this->baseModel);
	}
}
