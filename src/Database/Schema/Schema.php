<?php

namespace Framework\Database\Schema;

class Schema
{
	/**
	 * Keep track of all schemas inside the schema
	 *
	 * @var array
	 */
	private static $schemas = [];

	/**
	 * This will create a new schema for a database table
	 *
	 * @param string $table
	 * @param callable $callback
	 * @return string
	 */
	public static function create(string $table, callable $callback, string $chartset = 'utf8mb4', $collatie = 'utf8mb4_unicode_ci'): string
	{
		$callback($schema = new SchemaBuilder($table, $chartset, $collatie));

		static::$schemas[$table] = [
			'columns' => $schema->getColumns(),
			'schema' => $schema = $schema->compile()
		];

		return $schema;
	}

	/**
	 * This method will get all schemas
	 *
	 * @return array
	 */
	public static function getSchemas(): array
	{
		return self::$schemas;
	}
}
