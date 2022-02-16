<?php

namespace Framework\Model;

use Framework\Database\QueryBuilder\QueryBuilder;
use JsonSerializable;
use ArrayObject;
use Exception;
use stdClass;

/**
 * @method \Framework\Database\QueryBuilder\QueryBuilder logSql()
 * @method \Framework\Database\QueryBuilder\QueryBuilder table(string $table, string|array $select = ['*'])
 * @method \Framework\Database\QueryBuilder\QueryBuilder select(string|array $select)
 * @method \Framework\Database\QueryBuilder\QueryBuilder subQuery(Closure $query, string $before = '', string $after = '', bool $isWhereClause = false, string $boolean = 'AND')
 * @method \Framework\Database\QueryBuilder\QueryBuilder where(mixed $column, array|string $operator = null, $value = null, string $boolean = 'AND')
 * @method \Framework\Database\QueryBuilder\QueryBuilder whereRaw(string|Closure $query, array $bindData = [], string $boolean = 'AND')
 * @method \Framework\Database\QueryBuilder\QueryBuilder orWhere(mixed $column, ?string $operator = null, mixed $value = null)
 * @method \Framework\Database\QueryBuilder\QueryBuilder whereIn(string $column, array|Closure $value, string $boolean = 'AND')
 * @method \Framework\Database\QueryBuilder\QueryBuilder whereExists(Closure $callback, string $boolean = 'AND', bool $not = false)
 * @method \Framework\Database\QueryBuilder\QueryBuilder whereNotExists(Closure $callback, string $boolean = 'AND')
 * @method \Framework\Database\QueryBuilder\QueryBuilder whereColumn(string $column, ?string $operator = null, ?string $value = null, string $boolean = 'AND')
 * @method \Framework\Database\QueryBuilder\QueryBuilder join(string $table, string|Closure $first, ?string $operator = null, ?string $value = null, string $type = 'INNER')
 * @method \Framework\Database\QueryBuilder\QueryBuilder leftJoin(string $table, string|Closure $first, ?string $operator = null, ?string $value = null)
 * @method \Framework\Database\QueryBuilder\QueryBuilder rightJoin(string $table, string|Closure $first, ?string $operator = null, ?string $value = null)
 * @method \Framework\Database\QueryBuilder\QueryBuilder all(mixed $fallbackReturnValue = false, int $fetchMode = null)
 * @method \Framework\Database\QueryBuilder\QueryBuilder one(mixed $fallbackReturnValue = false, int $fetchMode = null)
 * @method \Framework\Database\QueryBuilder\QueryBuilder column(mixed $fallbackReturnValue = false, int $column = 0)
 * @method \Framework\Database\QueryBuilder\QueryBuilder insert(array $insertData)
 * @method \Framework\Database\QueryBuilder\QueryBuilder update(array $updateData)
 * @method \Framework\Database\QueryBuilder\QueryBuilder delete()
 * @method \Framework\Database\QueryBuilder\QueryBuilder raw(string $query, array $bindings = [])
 * @method \Framework\Database\QueryBuilder\QueryBuilder limit(int $limit)
 * @method \Framework\Database\QueryBuilder\QueryBuilder offset(int $offset)
 * @method \Framework\Database\QueryBuilder\QueryBuilder paginate(int $currentPage, int $perPage = 15)
 * @method \Framework\Database\QueryBuilder\QueryBuilder orderBy(string $column, string $direction = 'ASC')
 * @method \Framework\Database\QueryBuilder\QueryBuilder groupBy(string ...$groups)
 * @method \Framework\Database\QueryBuilder\QueryBuilder when(bool $when, callable $callback)
 *
 * @see \Framework\Database\QueryBuilder\QueryBuilder
 */
abstract class BaseModel extends ArrayObject implements JsonSerializable
{
    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * @var string $table
     */
    protected string $table = '';

    /**
     * @var object|null $original
     */
    private ?object $original = null;

    /**
     * @return QueryBuilder
     */
    final public function query(): QueryBuilder
    {
        return QueryBuilder::new()->table($this->getTableFromModel());
    }

    /**
     * @param mixed $find
     * @param string|null $key
     * @return mixed
     * 
     * @throws Exception
     *
     * @return mixed
     * 
     * @throws Exception
     */
    public function find(mixed $find, ?string $key = null): mixed
    {
        return $this->query()->where($key ?: $this->primaryKey, '=', $find)->one();
    }

    /**
     * @param QueryBuilder $builder
     * @param string $field
     * @param mixed $value
     * @return object|null
     */
    public function routeModelBinding(QueryBuilder $builder, string $field, mixed $value): ?object
    {
        return $builder->where($field, '=', $value)->one(null);
    }

    /**
     * @param object|null $original
     * @return void
     */
    public function setOriginal(?object $original): void
    {
        $this->original = $original;
    }

    /**
     * This will set the table to the model
     * 
     * @param string $table
     * @return string
     */
    public function setTable(string $table): string
    {
        return $this->table = $table;
    }

    /**
     * This will get the primary key
     * 
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * This will get the table name
     * 
     * @return string
     */
    public function getTable(): string
    {
        return $this->getTableFromModel();
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    private function getTableFromModel(): string
    {
        // when there is already a table set
        if (!empty($this->table)) {
            return $this->table;
        }

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
        return $this->setTable($table . 's');
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (!is_object($this->original)) {
            $this->original = new stdClass();
        }

        $this->original->{$name} = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->original->{$name});
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->original->{$name};
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        return call_user_func([
            $this->query()->table($this->getTableFromModel()),
            $name
        ], ...$arguments);
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode($this->original);
    }
}
