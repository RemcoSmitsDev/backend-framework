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
			return preg_replace('/^([A-z0-9_\-]+)\.([A-z0-9_\-]+)$/', ' `$1`.`$2`', $columnName);
		} else {
			return preg_replace('/^([A-z0-9_\-]+)$/', ' `$1`', $columnName);
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
	 * function logSqlQuery
	 * @param string $query
	 * @param array $bindings
	 * @param int|float $executionTime
	 * @return false|void
	 */
	public function logSqlQuery(string $query, array $bindings, int|float $executionTime)
	{
		// check if query log was on
		if (!$this->logSql || !defined('IS_DEVELOPMENT_MODE') || !IS_DEVELOPMENT_MODE) {
			return false;
		}

		// map trough formatted
		$formattedBindData = collection($bindings)->map(function ($item) {
			// check if data is bool
			if (is_bool($item)) {
				return $item ? 'true' : 'false';
			}

			// return item
			return $item;
		})->toArray();

		// check if ray is enabled
		if (app()->rayIsEnabled()) {
			// log inside ray
			ray(SqlFormatter::format($query), $formattedBindData, 'Execution time: ' . $executionTime . ' seconds')->type('query')->title('Database query');
		} else {
			// echo query
			echo $query . ' --- bindings: (' . implode(',', $formattedBindData) . ')<br>';
		}
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
