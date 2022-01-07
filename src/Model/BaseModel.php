<?php

namespace Framework\Model;

use Framework\Database\Database;
use Exception;
use Framework\Database\Relations\BelongsTo;
use Framework\Database\Relations\HasMany;
use ReflectionClass;
use ReflectionMethod;

abstract class BaseModel extends Database
{
    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * This will keep track of the relations that where set
     *
     * @var array
     */
    protected array $relations = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // get table base on model name
        $table = str_replace(
            'controller',
            '',
            strtolower(getClassName(get_class($this)))
        );

        // when ending with y replace with ie for the plural
        if (str_ends_with($table, 'y')) {
            $table = substr($table, 0, -1) . 'ie';
        }

        // set table name
        $this->from = $this->table ?? $table . 's';
        $this->columns = $this->columns ?: ['*'];

        // call database constructor
        parent::__construct();
    }

    /**
     * @param mixed $find
     * @param string|null $key
     * 
     * @return mixed
     * 
     * @throws Exception
     */
    public function find(mixed $find, ?string $key = null): mixed
    {
        return $this->where($key ?: $this->primaryKey, '=', $find)->one();
    }

    protected function belongsTo(string $belongsTo, $primaryKey = null, $table = null)
    {
        return new BelongsTo($this::class, $belongsTo, $primaryKey ?: $this->primaryKey, $table ?: $this->table);
    }

    protected function hasMany(string|object $hasMany, $primaryKey = null, $table = null)
    {
        return new HasMany($this::class, $hasMany, $primaryKey ?: $this->primaryKey, $table);
    }
}


// TODO:
// When you know the full table `posts` than when the table `posts` was selected by ->table('posts')
// the relation will be managed/applied
// 
// TODO: 
// I should also make a funtion `without` that function will remove relation