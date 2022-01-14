<?php

namespace Framework\Database\Schema;

use Framework\Collection\Collection;
use Framework\Container\Container;
use Exception;

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
	 * @return self
	 */
	public static function create(string $table, callable $callback, string $chartset = 'utf8mb4', $collatie = 'utf8mb4_unicode_ci'): self
	{
		$callback($schema = new SchemaBuilder($table, $chartset, $collatie));

		static::$schemas[$table] = [
			'columns' => $schema->getColumns(),
			'schema' => $schema,
			'drop' => fn () => static::drop($table)
		];

		return new self;
	}

	/**
	 * This method will get all schemas
	 *
	 * @return Collection
	 */
	public static function getSchemas(): Collection
	{
		// load all up table schemas
		self::loadAllSchemas()->each(function ($schemaClass) {
			// // get up arguments
			$arguments = Container::handleClassMethod($schemaClass::class, 'up');

			// // call up method
			$schemaClass->up(...$arguments);
		});

		return collection(self::$schemas);
	}

	public static function drop(string ...$tables): void
	{
		collection(static::getSchemas()->keys())
			->combine(self::loadAllSchemas())
			->filter(function ($schema, $table) use ($tables) {
				return in_array($table, $tables) || empty($tables);
			})->each(function ($schema) {
				$schema->down();
			});
	}

	/**
	 * This method will load all schemas
	 *
	 * @return Collection
	 */
	private static function loadAllSchemas(): Collection
	{
		// keep track of class instances
		$classes = [];

		// Loop trough all schemas
		foreach (collection(scandir(SERVER_ROOT . '/../database/schemas/') ?: [])->filter(fn ($val) => trim($val, '.')) as $file) {
			// get class name from file name
			$classname = str_replace('.php', '', $file);

			// make new reflection
			$reflection = new \ReflectionClass('Database\\Schemas\\' . $classname);

			// check if class exists
			if (!$reflection) {
				continue;
			}

			// check if `up` method exists
			if (!$reflection->getMethod('up')) {
				throw new Exception('Couldn\'t found up method inside the migration class');
			}

			// check if `down` method exists
			if (!$reflection->getMethod('down')) {
				throw new Exception('Couldn\'t found up method inside the migration class');
			}

			// define namespace
			$classname = 'Database\\Schemas\\' . $classname;

			// append to classes
			$classes[] = new $classname;
		}

		return collection($classes);
	}
}
