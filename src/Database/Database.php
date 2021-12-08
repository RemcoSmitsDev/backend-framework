<?php

namespace Framework\Database;

use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Database\Connection\Connection;

class Database extends QueryBuilder
{
    public function __construct(Connection $connection)
    {
        // call parent constructor
        parent::__construct($connection);
    }
}