<?php

namespace Framework\Model;

use Exception;
use Framework\Database\QueryBuilder\QueryBuilder;
use JsonSerializable;
use stdClass;

/**
 * @method static \Framework\Database\QueryBuilder\QueryBuilder logSql()
 * @method static \Framework\Database\QueryBuilder\QueryBuilder table(string $table, string|array $select = ['*'])
 * @method static \Framework\Database\QueryBuilder\QueryBuilder select(string|array $select)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder subQuery(Closure $query, string $before = '', string $after = '', bool $isWhereClause = false, string $boolean = 'AND')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder where(mixed $column, array|string $operator = null, $value = null, string $boolean = 'AND')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder whereRaw(string|Closure $query, array $bindData = [], string $boolean = 'AND')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder orWhere(mixed $column, ?string $operator = null, mixed $value = null)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder whereIn(string $column, array|Closure $value, string $boolean = 'AND')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder whereExists(Closure $callback, string $boolean = 'AND', bool $not = false)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder whereNotExists(Closure $callback, string $boolean = 'AND')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder whereColumn(string $column, ?string $operator = null, ?string $value = null, string $boolean = 'AND')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder join(string $table, string|Closure $first, ?string $operator = null, ?string $value = null, string $type = 'INNER')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder leftJoin(string $table, string|Closure $first, ?string $operator = null, ?string $value = null)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder rightJoin(string $table, string|Closure $first, ?string $operator = null, ?string $value = null)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder all(mixed $fallbackReturnValue = false, int $fetchMode = null)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder one(mixed $fallbackReturnValue = false, int $fetchMode = null)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder column(mixed $fallbackReturnValue = false, int $column = 0)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder insert(array $insertData)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder update(array $updateData)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder delete()
 * @method static \Framework\Database\QueryBuilder\QueryBuilder raw(string $query, array $bindings = [])
 * @method static \Framework\Database\QueryBuilder\QueryBuilder limit(int $limit)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder offset(int $offset)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder paginate(int $currentPage, int $perPage = 15)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder orderBy(string $column, string $direction = 'ASC')
 * @method static \Framework\Database\QueryBuilder\QueryBuilder groupBy(string ...$groups)
 * @method static \Framework\Database\QueryBuilder\QueryBuilder when(bool $when, callable $callback)
 *
 * @see \Framework\Database\QueryBuilder\QueryBuilder
 */
abstract class BaseModel implements JsonSerializable
{
    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * @var string
     */
    protected string $table = '';

    /**
     * @var object|null
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
     * @param mixed       $find
     * @param string|null $key
     *
     * @throws Exception
     *
     * @return object|null
     */
    public function find(mixed $find, ?string $key = null): ?object
    {
        return $this->query()->where($key ?: $this->primaryKey, '=', $find)->one(null);
    }

    /**
     * @param string $field
     * @param mixed  $value
     * @param array  $dynamicData
     *
     * @return object|null
     */
    public function routeModelBinding(string $field, mixed $value, array $dynamicData): ?object
    {
        return $this->find($value, $field);
    }

    /**
     * @param object|null $original
     *
     * @return void
     */
    public function setOriginal(?object $original): void
    {
        $this->original = $original;
    }

    /**
     * This will set the table to the model.
     *
     * @param string $table
     *
     * @return string
     */
    public function setTable(string $table): string
    {
        return $this->table = $table;
    }

    /**
     * This will get the primary key.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * This will get the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->getTableFromModel();
    }

    /**
     * @throws ReflectionException
     *
     * @return string
     * @return string
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
            $table = substr($table, 0, -1).'ie';
        }

        // set table name
        return $this->setTable($table.'s');
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
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
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->original->{$name});
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->original->{$name};
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        return call_user_func_array([
            $this->query()->table($this->getTableFromModel()),
            $name,
        ], $arguments);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments = []): mixed
    {
        return call_user_func_array([new static(), $name], $arguments);
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode($this->original);
    }
}
