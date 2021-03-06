<?php

declare(strict_types=1);

namespace Framework\Database\Connection;

use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Debug\Debug;
use Framework\Event\Event;
use PDO;
use PDOStatement;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class Connection
{
    /**
     * @var PDO|null
     */
    private ?PDO $pdo = null;

    /**
     * @var PDOStatement|null
     */
    public ?PDOStatement $statement = null;

    /**
     * @var int|float|string
     */
    protected int|float|string $executionTime = 0;

    /**
     * @var bool
     */
    private bool $failed = false;

    /**
     * @param string $username
     * @param string $password
     * @param string $databaseName
     * @param string $hostname
     * @param string $charset
     * @param int    $port
     */
    public function __construct(
        private string $username = 'root',
        private string $password = 'root',
        private string $databaseName = '',
        private string $hostname = 'localhost',
        private string $charset = 'utf8',
        private int $port = 3306,
    ) {
    }

    /**
     * function start.
     *
     * @return self
     */
    private function start(): self
    {
        // check if there already exists an connection
        if (isset($this->pdo)) {
            return $this;
        }

        // connection settings
        $connectionSettings = "mysql:host={$this->hostname};dbname={$this->databaseName};port={$this->port};charset={$this->charset}";

        // define options
        $options = [
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            PDO::ATTR_PERSISTENT         => true,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
        ];

        // try making connection to database
        try {
            $this->pdo = new PDO($connectionSettings, $this->username, $this->password, $options);
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            return $this;
        }
    }

    /**
     * @param QueryBuilder $query
     * @param int|null     $fetchMode
     *
     * @return PDOStatement
     */
    public function runSelect(QueryBuilder $query, ?int $fetchMode = null): PDOStatement
    {
        $this->handleExecution($query, ...$query->selectToSql($query));

        $this->statement->setFetchMode(...$this->getFetchModeArguments($query, $fetchMode));

        return $this->statement;
    }

    /**
     * @param QueryBuilder $query
     * @param int|null     $fetchMode
     *
     * @return array
     */
    private function getFetchModeArguments(QueryBuilder $query, ?int $fetchMode = null): array
    {
        $fetchMode = $query->fromModel ? \PDO::FETCH_CLASS : ($fetchMode ?: $query->fetchMode);

        $args = [
            $fetchMode | \PDO::FETCH_PROPS_LATE,
        ];

        return $query->fromModel ? array_merge($args, [$query->fromModel::class]) : $args;
    }

    /**
     * This method will handle query execution.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $query
     * @param array        $bindings
     *
     * @return self
     */
    public function handleExecution(QueryBuilder $queryBuilder, string $query, array $bindings = []): self
    {
        // set failed to false
        $this->failed = false;

        // set start time for execution measure
        $this->executionTime = microtime(true);

        // try to execute query
        try {
            // prepare and execute query
            $this->prepare($query)->execute(
                array_values($bindings) ?: null
            );
        } catch (\Throwable $th) {
            // throw error this will get catch with the debug page
            Debug::add('errors', $th);

            // set failed to true
            $this->failed = true;
        } finally {
            // calc query execution time
            $this->calcExecutionTime();
        }

        // notify database event
        Event::notify('database-query', [
            'show'            => $queryBuilder->logSql,
            'pdo'             => $this->pdo,
            'builder'         => $queryBuilder,
            'statement'       => $this->statement,
            'query'           => $query,
            'bindings'        => $bindings,
            'executionTime'   => $this->executionTime(),
            'failed'          => $this->failed(),
            'effectedRows'    => (int) ($this->statement?->rowCount() ?? 0),
            'hasEffectedRows' => $this->hasEffectedRows(),
            'error'           => $this->failed() ? $th->getMessage() : false,
        ]);

        return $this;
    }

    /**
     * @param string $query
     *
     * @return self
     */
    public function prepare(string $query): self
    {
        $this->statement = $this->start()->pdo->prepare($query);

        return $this;
    }

    /**
     * @param array|null $bindData
     *
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
     * function close.
     *
     * @return void
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    /**
     * This method will check if there where rows effected by the last sql query.
     *
     * @return bool
     */
    public function hasEffectedRows(): bool
    {
        return ((int) $this->statement?->rowCount()) > 0;
    }

    /**
     * This method will return the failed status.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->failed;
    }

    /**
     * This method will calculate the query execution time.
     *
     * @return void
     */
    private function calcExecutionTime(): void
    {
        // get end time
        $endTime = microtime(true);

        // calc execution time
        $this->executionTime = number_format(($endTime - $this->executionTime()) * 10000, 2, '.', '');
    }

    /**
     * This method will return the execution time.
     *
     * @return int|float|string
     */
    public function executionTime(): int|float|string
    {
        return $this->executionTime;
    }

    /**
     * close connection.
     */
    public function __destruct()
    {
        // close connection
        $this->close();
    }
}
