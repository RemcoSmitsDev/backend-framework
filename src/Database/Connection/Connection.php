<?php

namespace Framework\Database\Connection;

use PDOException;

class Connection
{
    protected \PDO $connection;

    private string $username;
    private string $password;
    private string $hostname;
    private string $databaseName;

    /**
     * function __construct
     * @param string $username
     * @param string $password
     * @param string $databaseName
     * @param string $hostname
     */

    public function __construct(string $username = 'root', string $password = 'root', string $databaseName = '', string $hostname = 'localhost')
    {
        $this->username = $username;
        $this->password = $password;
        $this->databaseName = $databaseName;
        $this->hostname = $hostname;
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
        $connectionSettings = "mysql:host={$this->hostname};dbname={$this->databaseName};port=8889";

        // define options
        $options = [
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
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