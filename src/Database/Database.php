<?php

namespace Framework\Database;

use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Database\Connection\Connection;
use JetBrains\PhpStorm\Pure;

class Database extends QueryBuilder
{
    /**
     * @param Connection $connection
     */
    #[Pure]
    public function __construct(Connection $connection)
    {
        // call parent constructor
        parent::__construct($connection);
    }
}