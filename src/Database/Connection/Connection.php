<?php

namespace Framework\Database\Connection;

use PDOStatement;
use PDO;

class Connection
{
    /**
     * keeps track of connection
     * @var PDO|null
     */
    private ?PDO $pdo;

    /**
     * @var false|PDOStatement
     */
    public false|PDOStatement $statement;

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
        return $this->start()->statement->execute($bindData);
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
     * close connection
     */
    public function __destruct()
    {
        // close connection
        $this->close();
    }
}
