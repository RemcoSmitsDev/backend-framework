<?php

namespace Framework\Database;

use \Framework\Database\QueryBuilder\QueryBuilder;

trait DatabaseHelpers
{
	/**
	 * function formatColumnNames
	 * @param string $columnName
	 * @return string
	 */
	public function formatColumnNames(string $columnName): string
	{
		if (strpos($columnName, '.') !== false) {
			return preg_replace('/^([A-z0-9_\-]+)\.([A-z0-9_\-]+)$/', '`$1`.`$2`', $columnName);
		} else {
			return preg_replace('/^([A-z0-9_\-]+)$/', '`$1`', $columnName);
		}
	}

	/**
	 * function selectFormat
	 * @param mixed $selectColumn
	 * @return array
	 */
	protected function selectFormat(mixed $selectColumn): array
	{
		// keep track of select columns
		$selectColumns = [];

		// check if select columns is an array
		if (is_array($selectColumn)) {
			// loop trough all select columns
			foreach ($selectColumn as $column) {
				// merge select columns
				$selectColumns = array_merge($selectColumns, $this->selectFormat($column));
			}
		} else {
			$selectColumns[] = $selectColumn;
		}

		// return selectColumns
		return $selectColumns;
	}

	/**
	 * @param QueryBuilder $mainQuery
	 * @param QueryBuilder $mergeQuery
	 */
	public function mergeBindings(QueryBuilder $mainQuery, QueryBuilder $mergeQuery)
	{
		// loop through all bindings
		foreach ($mergeQuery->bindings as $key => $binding) {
			// merge binding
			$mainQuery->bindings[$key] = array_merge($mainQuery->bindings[$key], $binding);
		}
	}
}
