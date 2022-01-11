<?php

namespace Framework\Database\Schema;

use Framework\Collection\Collection;
use InvalidArgumentException;

class SchemaBuilder
{
	private array $columns = [];

	public function __construct(
		private string $table,
		private string $chartset,
		private string $collatie,
		array $columns = []
	) {
		$this->columns = $columns ?: $this->columns;
	}

	/**
	 * This method will create a column named `id` with autoincrement
	 *
	 * @param string $column
	 * @param integer $length
	 * @return SchemaColumn
	 */
	public function id(string $column = 'id', int $length = 20): SchemaColumn
	{
		return $this->bigInt($column, $length)->autoIncrement();
	}

	/**
	 * This method will create a new column `varchar`
	 *
	 * @param string $column
	 * @param integer $length
	 * @return SchemaColumn
	 */
	public function string(string $column, int $length = 255): SchemaColumn
	{
		return new SchemaColumn(
			column: $column,
			type: 'varchar',
			length: $length,
			collatie: $this->collatie,
			columns: $this->columns
		);
	}

	/**
	 * This method will create a new column `text` or `longtext`
	 *
	 * @param string $column
	 * @param boolean $isLong
	 * @return SchemaColumn
	 */
	public function text(string $column, bool $isLong = false): SchemaColumn
	{
		return new SchemaColumn(
			column: $column,
			type: $isLong ? 'longtext' : 'text',
			length: null,
			collatie: $this->collatie,
			columns: $this->columns
		);
	}

	/**
	 * This method will create a new column `json`
	 *
	 * @param string $column
	 * @return SchemaColumn
	 */
	public function json(string $column): SchemaColumn
	{
		return new SchemaColumn(
			column: $column,
			type: 'varchar',
			length: null,
			collatie: $this->collatie,
			columns: $this->columns
		);
	}

	/**
	 * This method will create a new column `$type`
	 *
	 * @param string $column
	 * @param integer $length
	 * @param integer $type
	 * @return SchemaColumn
	 */
	public function int(string $column, int $length = 11, string $type = 'int'): SchemaColumn
	{
		return new SchemaColumn(
			column: $column,
			type: $type,
			length: $length,
			collatie: null,
			columns: $this->columns
		);
	}

	/**
	 * This method will create a new column `type`
	 *
	 * @param string $column
	 * @param integer $length
	 * @return SchemaColumn
	 */
	public function tinyInt(string $column, int $length = 4): SchemaColumn
	{
		return $this->int($column, $length, 'tinyint');
	}

	/**
	 * This method will create a new column `bigint`
	 *
	 * @param string $column
	 * @param integer $length
	 * @return SchemaColumn
	 */
	public function bigInt(string $column, int $length = 20): SchemaColumn
	{
		return $this->int($column, $length, 'bigint');
	}

	/**
	 * This method will create a new column `float`
	 *
	 * @param string $column
	 * @return SchemaColumn
	 */
	public function float(string $column): SchemaColumn
	{
		return new SchemaColumn(
			column: $column,
			type: 'float',
			length: null,
			collatie: null,
			columns: $this->columns
		);
	}

	public function timestamp(string $column): SchemaColumn
	{
		return new SchemaColumn(
			column: $column,
			type: 'timestamp',
			length: null,
			collatie: null,
			columns: $this->columns
		);
	}

	public function getColumns(): array
	{
		return Collection::make($this->columns)->map(function (SchemaColumn $column) {
			return $column->getColumn();
		})->toArray();
	}

	public function compile(): string
	{
		if (empty($this->columns)) {
			throw new InvalidArgumentException('You must add more columns then one to the schema!');
		}

		// make new collection
		$columnsCollection = Collection::make($this->columns);

		// get/find primaryKey
		$primaryKey = $columnsCollection->filter(function ($item) {
			return $item->isAutoIncrement();
		})->first();

		// get all indexes
		$indexes = $columnsCollection->filter(function ($item) {
			return !empty($item->getIndex());
		})->map(function ($item) {
			return $item->getIndex();
		})->toArray();

		// get unique columns
		$uniqueColumns = $columnsCollection->filter(function ($item) {
			return $item->isUnique();
		})->map(function ($item) {
			return "UNIQUE KEY `{$this->table}_{$item->getColumn()}_unique` (`{$item->getColumn()}`)";
		})->toArray();

		// get all columns
		$columns = $columnsCollection->map(function ($item) {
			return $item->toString();
		})->toArray();

		// get all rows formatted to a string
		$rows = collection([
			$columns,
			$primaryKey ? "PRIMARY KEY (`{$primaryKey->getColumn()}`)" : null,
			$indexes,
			$uniqueColumns,
		])->filter()->flatten();

		// return create schema
		return "
			CREATE TABLE `{$this->table}` (
				{$rows}
			) ENGINE=InnoDB DEFAULT CHARSET={$this->chartset} COLLATE={$this->collatie};
		";
	}
}
