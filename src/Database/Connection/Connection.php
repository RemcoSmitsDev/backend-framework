<?php

namespace Framework\Database\Connection;

class Connection
{
    protected \PDO $connection;

    private string $username;
    private string $password;
    private string $hostname;
    private string $databaseName;

    public function __construct(string $username = 'root', string $password = 'root', string $databaseName = 'db_datastromen', string $hostname = 'localhost')
    {
        $this->username = $username;
        $this->password = $password;
        $this->databaseName = $databaseName;
        $this->hostname = $hostname;
    }

    public function start()
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
        } catch (\PDOExeption $e) {
            // echo $e->getMessage();
        }

        return $this->connection;
    }

    public function close()
    {
        $this->connection = null;
    }
}
