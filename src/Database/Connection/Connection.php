<?php

namespace Framework\Database\Connection;

use Framework\Database\QueryBuilder\QueryBuilder;
use PDOStatement;
use PDO;

class Connection
{
	/**
	 * @var PDO|null
	 */
	private ?PDO $pdo;

	/**
	 * @var false|PDOStatement
	 */
	public false|PDOStatement $statement;

	/**
	 * @var int|float
	 */
	protected int|float $executionTime = 0;

	/**
	 * @var boolean
	 */
	private bool $failed;

	/**
	 * function __construct
	 * @param string $username
	 * @param string $password
	 * @param string $databaseName
	 * @param string $hostname
	 * @param string $charset
	 * @param int $port
	 */
	public function __construct(
		private string $username = 'root',
		private string $password = 'root',
		private string $databaseName = '',
		private string $hostname = 'localhost',
		private string $charset = 'utf8',
		private int    $port = 3306,
	) {
	}

	/**
	 * function start
	 * @return Connection
	 */
	public function start(): self
	{
		// check if there already exists an connection
		if (isset($this->pdo)) {
			return $this;
		}

		// connection settings
		$connectionSettings = "mysql:host={$this->hostname};dbname={$this->databaseName};port={$this->port};charset={$this->charset}";

		// define options
		$options = [
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
		];

		// try making connection to database
		try {
			$this->pdo = new PDO($connectionSettings, $this->username, $this->password, $options);
		} finally {
			return $this;
		}
	}

	/**
	 * This method will handle query execution
	 *
	 * @param QueryBuilder $queryBuilder
	 * @param string $query
	 * @param array $bindings
	 * @return mixed
	 */
	public function handleExecution(QueryBuilder $queryBuilder, string $query, array $bindings = []): mixed
	{
		// set failed to false
		$this->failed = false;

		// set start time for execution measure
		$this->executionTime = microtime(true) * 100;

		// prepare query
		$this->prepare($query);

		// try to execute query
		try {
			// execute query
			$this->execute(
				array_values($bindings) ?: null
			);
		} catch (\Throwable $th) {
			// set failed to true
			$this->failed = true;
		} finally {
			// calc query execution time
			$this->calcExecutionTime();
		}

		// check if need to log query
		if ($queryBuilder->logSql) {
			$queryBuilder->logSqlQuery($query, $bindings, $this->executionTime());
		}

		// reset all props
		$queryBuilder->reset();

		// return query builder
		return $this;
	}

	/**
	 * @param string $query
	 * @return Connection
	 */
	public function prepare(string $query): self
	{
		$this->statement = $this->start()->pdo->prepare($query);

		return $this;
	}

	/**
	 * @param array|null $bindData
	 * @return bool
	 */
	public function execute(?array $bindData): bool
	{
		return $this->statement->execute($bindData);
	}

	/**
	 * @return string
	 */
	public function insertId(): string
	{
		return $this->pdo->lastInsertId();
	}

	/**
	 * function close
	 * @return void
	 */
	public function close(): void
	{
		$this->pdo = null;
	}

	/**
	 * This method will return the failed status
	 *
	 * @return boolean
	 */
	public function failed(): bool
	{
		return $this->failed;
	}

	/**
	 * This method will calculate the query execution time
	 *
	 * @return void
	 */
	private function calcExecutionTime(): void
	{
		// get end time
		$endTime = microtime(true) * 100;

		// calc execution time
		$this->executionTime = round($endTime - $this->executionTime(), 1) / 100;
	}

	/**
	 * This method will return the execution time
	 *
	 * @return integer|float
	 */
	public function executionTime(): int|float
	{
		return $this->executionTime;
	}

	/**
	 * close connection
	 */
	public function __destruct()
	{
		// close connection
		$this->close();
	}
}
