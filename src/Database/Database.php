<?php

namespace Framework\Database;

use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Database\Connection\Connection;
use ReflectionException;
use Exception;

class Database extends QueryBuilder
{
    /**
     * @param Connection|null $connection
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(?Connection $connection = null)
    {
        // check if there is an connection
        if(!$connection){
            $connection = app('connection');
        }

        // throw exception when there is no connection
        if(!$connection){
            throw new Exception('You must first make connection to the database!');
        }

        // call parent constructor
        parent::__construct($connection);
    }
}