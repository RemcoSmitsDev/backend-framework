<?php

namespace Framework\Database\Connection;

use PDOException;

class Connection
{
    /**
     * keeps track of connection
     * @var \PDO
     */

    protected \PDO $connection;

    /**
     * function __construct
     * @param string $username
     * @param string $password
     * @param string $databaseName
     * @param string $hostname
     */

    public function __construct(
        private string $username = 'root',
        private string $password = 'root',
        private string $databaseName = '',
        private string $hostname = 'localhost',
        private string $chartset = 'utf8',
        private int $port = 3306,
    ) {
    }

    /**
     * function start
     * @return bool|\PDO
     */

    public function start(): bool|\PDO
    {
        // check if there already exists an connection
        if (isset($this->connection)) {
            return false;
        }

        // connection settings
        $connectionSettings = "mysql:host={$this->hostname};dbname={$this->databaseName};port={$this->port};charset={$this->chartset}";

        // define options
        $options = [
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->chartset}"
        ];

        // try making connection to database
        try {
            $this->connection = new \PDO($connectionSettings, $this->username, $this->password, $options);
        } finally {
            return $this->connection;
        }
    }

    /**
     * function close
     * @return void
     */

    public function close(): void
    {
        $this->connection = null;
    }
}