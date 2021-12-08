<?php

namespace Framework\Model;

use Framework\Database\Database;
use Exception;

class BaseModel extends Database
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        // get table base on model name
        $table = str_replace(
            'controller',
            '',
            strtolower(get_class($this))
        );

        // set table name
        $this->table($table.'s');

        // call database constructor
        parent::__construct();
    }
}