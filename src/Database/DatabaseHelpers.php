<?php

namespace Framework\Database;

use \Framework\Database\QueryBuilder\QueryBuilder;
use Exception;
use Closure;

trait DatabaseHelpers
{
	/**
	 * valid query types
	 * @var array
	 */
	private array $validTypes = [
		'insert',
		'update',
		'select',
		'delete',
		'truncate',
		'drop',
		'describe'
	];

	/**
	 * function formatColumnNames
	 * @param string $columnName
	 * @return string
	 */
	public function formatColumnNames(string $columnName): string
	{
		return preg_replace('/^([A-z0-9_\-]+)\.([A-z0-9_\-]+)$/', ' `$1`.`$2`', $columnName);
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
	 * function whereFormat
	 * @param array $where
	 * @return array
	 */
	protected function whereFormat(array $where): array
	{
		// keep track of wheres
		$wheres = [];

		// loop trough all wheres
		foreach ($where as $key => $value) {
			// check if key is an string
			if (is_string($key)) {
				$wheres[$key] = $value;
			} else {
				// merge wheres
				$keys = array_keys($value);
				$values = array_values($where[$key]);

				// loop trough all keys
				foreach ($keys as $key => $value) {
					$wheres[$value] = $values[$key];
				}
			}
		}

		// return wheres
		return $wheres;
	}

	/**
	 * function whereClosure
	 * @param Closure $column
	 * @param string $boolean
	 * @return DatabaseHelpers|QueryBuilder
	 */
	protected function whereClosure(Closure $column, string $boolean): self
	{
		// call closure with new instance of database
		$column($query = new QueryBuilder($this->connection));

		// check if there is an where statement
		if (isset($query->wheres[0])) {
			// update boolean when is sub query
			$query->wheres[0]['boolean'] = $boolean;
		}

		// get wheres from sub group
		$this->wheres[] = array_merge(
			(array)$this->wheres,
			(array)$query->wheres
		);

		// merge bindings
		$this->mergeBindings($this, $query);

		// return self
		return $this;
	}

	/**
	 * function createSubSelect
	 * @param string|Closure|Database $query
	 * @return array
	 * @throws Exception
	 */
	protected function createSubSelect(string|Closure|Database $query): array
	{
		// check if query is instance of \Closure
		if ($query instanceof Closure) {
			// make instance of DatabaseClass
			$query($query = new QueryBuilder($this->connection));

			// merge bindings
			foreach ($query->bindings as $key => $binding) {
				$this->bindings[$key] = array_merge($this->bindings[$key], $binding);
			}
		}

		// return formatted
		return $this->parseSub($query);
	}

	/**
	 * function parseSub
	 * @param mixed $query
	 * @return array
	 * @throws Exception
	 */
	protected function parseSub(mixed $query): array
	{
		if ($query instanceof QueryBuilder) {
			// return formatted query string with bindings
			return $query->selectToSql($query);
		} elseif (is_string($query)) {
			// return query string with empty bindings
			return [$query, []];
		} else {
			throw new Exception("The sub query must be an instanceof Database or an string", 1);
		}
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
		$formattedBindData = collection($bindings)->map(function ($item, $key) {
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
