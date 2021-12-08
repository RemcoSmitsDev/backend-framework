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

        // when ending with y replace with ie for the plural
        if(str_ends_with($table,'y')){
            $table = substr($table, 0, -1).'ie';
        }

        // set table name
        $this->table($table.'s');

        // call database constructor
        parent::__construct();
    }
}