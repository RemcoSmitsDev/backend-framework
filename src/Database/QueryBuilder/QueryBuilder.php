<?php

namespace Framework\Database\QueryBuilder;

use ArrayIterator;
use Closure;
use Exception;
use Framework\Database\Connection\Connection;
use Framework\Database\DatabaseHelpers;
use Framework\Database\Grammar\Grammar;
use Framework\Database\QueryBuilder\JoinClause\JoinClause;
use Framework\Database\QueryBuilder\Paginator\Paginator;
use Framework\Database\QueryBuilder\SubQuery\SubQuery;
use IteratorAggregate;

class QueryBuilder extends Grammar implements IteratorAggregate
{
    use DatabaseHelpers;

    /**
     * keeps track of show query's.
     *
     * @var bool
     */
    public bool $logSql = false;

    /**
     * fetch mode.
     *
     * @var int
     */
    protected int $fetchMode = \PDO::FETCH_OBJ;

    /**
     * builder parts for making database query.
     *
     * @var array
     */
    protected array $bindings = [
        'select'  => [],
        'from'    => [],
        'join'    => [],
        'where'   => [],
        'groupBy' => [],
        'orderBy' => [],
    ];

    /**
     * keeps track of select columns.
     *
     * @var array
     */
    public array $columns = [];

    /**
     * keep track of main table name.
     *
     * @var string
     */
    public string $from = '';

    /**
     * keeps track of joins.
     *
     * @var array
     */
    public array $joins = [];

    /**
     * keeps track of where statements.
     *
     * @var array
     */
    public array $wheres = [];

    /**
     * keeps track of limit amount.
     *
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * keeps track of offset.
     *
     * @var int
     */
    public ?int $offset = null;

    /**
     * keeps track of all group statements.
     *
     * @var array
     */
    public array $groups = [];

    /**
     * keeps track of all order statements.
     *
     * @var array
     */
    public array $orders = [];

    /**
     * @var array|null
     */
    private ?array $resetData;

    /**
     * @var Connection|null
     */
    private ?Connection $connection;

    /**
     * @var bool
     */
    public bool $isRaw = false;

    /**
     * @var array
     */
    public array $rawQuery = [];

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        // set connection class instance
        $this->connection = $connection;

        // set reset data
        $this->resetData = get_object_vars($this);
    }

    /**
     * @param Connection|null $connection
     *
     * @return QueryBuilder
     */
    public static function new(?Connection $connection = null): QueryBuilder
    {
        return new self($connection ?: app(Connection::class) ?: new Connection());
    }

    /**
     * @return $this
     */
    public function logSql(): self
    {
        // set logSql to true
        $this->logSql = true;

        // return self
        return $this;
    }

    /**
     * select table with option for select columns.
     *
     * @param string       $tableName
     * @param string|array $select
     *
     * @throws Exception
     *
     * @return self
     */
    public function table(string $tableName, string|array $select = ['*']): self
    {
        // add table name
        $this->from = $tableName;

        // reset columns
        $this->columns = [];

        // make select statement
        // and return self
        return $this->select($select);
    }

    /**
     * Select columns from table.
     *
     * @param string|array $select
     *
     * @throws Exception
     *
     * @return self
     */
    public function select(string|array $select): self
    {
        // check if column was already set(when your use table()->select())
        if (($key = $this->checkIfColumnWasAlreadySet('*')) !== false) {
            unset($this->columns[$key]);
        }

        // make select
        $columns = is_string($select) ? (is_array($select) ? $select : func_get_args()) : $select;

        // loop through all columns
        foreach ($columns as $as => $column) {
            // check if is subSelect with as name
            if ($column instanceof Closure) {
                // make subSelect
                $this->columns[] = $this->subQuery(
                    $column,
                    after: (is_string($as) ? ' as '.$as : '')
                );
            } elseif ($column instanceof SubQuery) {
                $this->columns[] = $column->setAfter(is_string($as) ? $as : $column->getAfter());
            }
            // when column is an array
            elseif (is_array($column)) {
                $this->columns = array_merge(
                    $this->columns,
                    $this->selectFormat($column)
                );
            } // else is string
            else {
                // add column to columns array
                $this->columns[] = $this->formatColumnNames(trim($column));
            }
        }

        // make select columns unique
        $this->columns = array_unique($this->columns);

        // return self
        return $this;
    }

    /**
     * This method will check if an column already exists.
     *
     * @param string $column
     *
     * @return bool|int
     */
    private function checkIfColumnWasAlreadySet(string $column): bool|int
    {
        // find column
        $key = array_search($column, $this->columns);

        // check if column was found
        return $key === false ? false : $key;
    }

    /**
     * function subQuery.
     *
     * @param Closure $query
     * @param string  $before
     * @param string  $after
     *
     * @throws Exception
     *
     * @return SubQuery
     */
    public function subQuery(Closure $query, string $before = '', string $after = '', bool $isWhereClause = false, string $boolean = 'AND'): SubQuery
    {
        // call callback
        $query($query = new QueryBuilder($this->connection));

        // merge bindings
        $this->mergeBindings($this, $query);

        // get bindings from query
        return new SubQuery($this, $query, $before, $after, $isWhereClause, $boolean);
    }

    //
    // WHERE functions
    //

    /**
     * function where.
     *
     * @param mixed             $column
     * @param array|string|null $operator
     * @param null              $value
     * @param string            $boolean
     *
     * @return self
     */
    public function where(mixed $column, array|string $operator = null, $value = null, string $boolean = 'AND'): self
    {
        // check is instanceof \Closure
        if ($column instanceof Closure && is_null($value)) {
            // return self and handle whereClosure
            $this->wheres[] = $this->subQuery($column, isWhereClause: true, boolean: $boolean);

            return $this;
        }

        // when operator is value
        if (is_null($value) && !is_null($operator)) {
            // make operator the value
            $value = $operator;
            // reset operator to '='
            $operator = '=';
        }

        // make array of columns/values
        $columns = (array) $column;
        $values = (array) $value;

        // loop through all columns
        foreach ($columns as $key => $column) {
            // get value by column
            $value = $values[$key] ?? null;

            // check if operator is an array
            if (is_array($operator)) {
                $_operator = $operator[$key] ?? '=';
            }

            // force to be real int
            if (is_int($value)) {
                $value = (int) $value;
            }

            // add to where statement
            $this->wheres[] = [
                'type'     => 'normal',
                'column'   => $column,
                'operator' => $_operator ?? $operator,
                'boolean'  => $boolean,
            ];

            // add value binding
            $this->bindings['where'][] = $value;
        }

        // return self
        return $this;
    }

    /**
     * function whereRaw.
     *
     * @param string|Closure $query
     * @param mixed          $bindData
     * @param string         $boolean
     *
     * @return QueryBuilder
     */
    public function whereRaw(string|Closure $query, array $bindData = [], string $boolean = 'AND'): static
    {
        // check query type
        if (is_string($query)) {
            // keep track of query type
            $type = 'raw';

            // add bindings
            $this->bindings['where'][] = $bindData;
        } else {
            $type = 'whereRaw';

            // make sub query
            $this->wheres[] = $this->subQuery($query, isWhereClause: true, boolean: $boolean);
        }

        // add to where statement
        $this->wheres[] = compact(
            'type',
            'query',
            'boolean'
        );

        // return self
        return $this;
    }

    /**
     * function orWhere.
     *
     * @param mixed       $column   Column names from tables
     * @param string|null $operator
     * @param mixed|null  $value
     *
     * @return self
     */
    public function orWhere(mixed $column, ?string $operator = null, mixed $value = null): self
    {
        // return self and make where statement with OR
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * function whereIn.
     *
     * @param string        $column
     * @param array|Closure $values
     * @param string        $boolean
     *
     * @throws Exception
     *
     * @return self
     */
    public function whereIn(string $column, array|Closure $value, string $boolean = 'AND'): self
    {
        // formate columns name
        $column = $this->formatColumnNames($column);

        // check if $column is instance of closure that means that whereIn will be an subWhere
        // the where statement will have ( ) wrapped around it
        if ($value instanceof Closure) {
            // add to where statement
            $this->wheres[] = $this->subQuery($value, before: "{$column} IN", isWhereClause: false, boolean: $boolean);
        } else {
            // add to where statement
            $this->wheres[] = [
                'type'    => 'raw',
                'query'   => "{$column} IN (?)",
                'boolean' => $boolean,
            ];

            // add values to where bindings
            $this->bindings['where'][] = implode(',', $value);
        }

        // return self
        return $this;
    }

    /**
     * function whereExists.
     *
     * @param Closure $callback
     * @param string  $boolean
     * @param bool    $not
     *
     * @throws Exception
     *
     * @return self
     */
    public function whereExists(Closure $callback, string $boolean = 'AND', bool $not = false): self
    {
        // get type based on not value
        $type = $not ? 'notExists' : 'EXISTS';

        // call closure
        $this->wheres[] = $this->subQuery($callback, before: $type);

        // return self
        return $this;
    }

    /**
     * function whereNotExists.
     *
     * @param Closure $callback
     * @param string  $boolean
     *
     * @throws Exception
     *
     * @return self
     */
    public function whereNotExists(Closure $callback, string $boolean = 'AND'): self
    {
        return $this->whereExists($callback, $boolean, true);
    }

    /**
     * function whereColumn.
     *
     * @param string      $column
     * @param string|null $operator
     * @param string|null $value
     * @param string      $boolean
     *
     * @return QueryBuilder
     */
    public function whereColumn(string $column, ?string $operator = null, ?string $value = null, string $boolean = 'AND'): static
    {
        // add to where statement
        $this->wheres[] = [
            'type'     => 'column',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value,
            'boolean'  => $boolean,
        ];

        // return self
        return $this;
    }

    //
    // Join methods
    //

    /**
     * @param string         $table
     * @param string|Closure $first
     * @param string|null    $operator
     * @param string|null    $value
     * @param string         $type
     *
     * @return $this
     */
    public function join(string $table, string|Closure $first, ?string $operator = null, ?string $value = null, string $type = 'INNER'): self
    {
        // make instance of
        $join = new JoinClause($this, $table, $type);

        // check if first is instance of closure
        if ($first instanceof Closure) {
            // make closure and make instance of JoinClause
            $first($join);

            // add join query
            $this->joins[] = $join;
        } else {
            $this->joins[] = $join->on($first, $operator, $value);
        }

        // merge bindings
        $this->mergeBindings($this, $join);

        // return self
        return $this;
    }

    /**
     * function leftJoin.
     *
     * @param string         $table
     * @param string|Closure $first
     * @param string|null    $operator
     * @param string|null    $value
     *
     * @return QueryBuilder
     */
    public function leftJoin(string $table, string|Closure $first, ?string $operator = null, ?string $value = null): self
    {
        return $this->join($table, $first, $operator, $value, 'left');
    }

    /**
     * function rightJoin.
     *
     * @param string         $table
     * @param string|Closure $first
     * @param string|null    $operator
     * @param string|null    $value
     *
     * @return QueryBuilder
     */
    public function rightJoin(string $table, string|Closure $first, ?string $operator = null, ?string $value = null): self
    {
        return $this->join($table, $first, $operator, $value, 'right');
    }

    //
    // Fetch methods
    //

    /**
     * function all.
     *
     * @param mixed    $fallbackReturnValue
     * @param int|null $fetchMode
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function all(mixed $fallbackReturnValue = [], int $fetchMode = null): mixed
    {
        // handle execution
        $connection = $this->connection->handleExecution(
            $this,
            ...($this->isRaw ? $this->rawQuery : $this->selectToSql($this))
        );

        // return fallback return value
        return ($connection->failed() || !$connection->hasEffectedRows()) ? $fallbackReturnValue : $connection->statement->fetchAll($fetchMode ?: $this->fetchMode);
    }

    /**
     * function one.
     *
     * @param mixed    $fallbackReturnValue
     * @param int|null $fetchMode
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function one(mixed $fallbackReturnValue = false, int $fetchMode = null): mixed
    {
        // handle execution
        $connection = $this->connection->handleExecution(
            $this->limit(1),
            ...($this->isRaw ? $this->rawQuery : $this->selectToSql($this))
        );

        // return fallback return value
        return ($connection->failed() || !$connection->hasEffectedRows()) ? $fallbackReturnValue : $connection->statement->fetch($fetchMode ?: $this->fetchMode);
    }

    /**
     * function column.
     *
     * @param mixed $fallbackReturnValue
     * @param int   $column
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function column(mixed $fallbackReturnValue = false, int $column = 0): mixed
    {
        // handle execution
        $connection = $this->connection->handleExecution(
            $this,
            ...($this->isRaw ? $this->rawQuery : $this->selectToSql($this))
        );

        // return fallback return value
        return $connection->failed() ? $fallbackReturnValue : $connection->statement->fetchColumn($column);
    }

    //
    // action methods
    //

    /**
     * function insert.
     *
     * @param array $insertData
     *
     * @throws Exception
     *
     * @return bool|int
     */
    public function insert(array $insertData): bool|int
    {
        // check if there exists an table
        if (empty($insertData && $this->from)) {
            return false;
        }

        // handle execution
        $connection = $this->connection->handleExecution(
            $this,
            ...($this->isRaw ? $this->rawQuery : $this->insertToSql($this, $insertData))
        );

        // return insert id(s) or false when execution was failed
        return $connection->failed() || !$connection->hasEffectedRows() ? false : $connection->insertId();
    }

    /**
     * function update.
     *
     * @param array $updateData
     *
     * @throws Exception
     *
     * @return bool
     */
    public function update(array $updateData): bool
    {
        // check if update data is empty
        if (empty($updateData && $this->from)) {
            return false;
        }

        // handle execution
        $connection = $this->connection->handleExecution(
            $this,
            ...($this->isRaw ? $this->rawQuery : $this->updateToSql($this, $updateData))
        );

        // return true or false (based on status of execution)
        return !$connection->failed() && $connection->hasEffectedRows();
    }

    /**
     * function update.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete(): bool
    {
        // check if update data is empty
        if (empty($this->from)) {
            return false;
        }

        // handle execution
        $connection = $this->connection->handleExecution(
            $this,
            ...($this->isRaw ? $this->rawQuery : $this->deleteToSql($this))
        );

        // return true or false (based on status of execution)
        return !$connection->failed() && $connection->hasEffectedRows();
    }

    /**
     * function raw.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function raw(string $query, array $bindings = []): mixed
    {
        // set raw is true
        $this->isRaw = true;

        // set raw query
        $this->rawQuery = [$query, $bindings];

        // return self
        return $this;
    }

    //
    // helpers
    //

    /**
     * function limit.
     *
     * @param int $limit
     *
     * @return self
     */
    public function limit(int $limit): self
    {
        // add limit to builder parts
        $this->limit = $limit;

        // check if offset was set
        if (!isset($this->offset)) {
            // set offset
            return $this->offset(0);
        }

        // return self
        return $this;
    }

    /**
     * function offset.
     *
     * @param int $offset
     *
     * @return self
     */
    public function offset(int $offset): self
    {
        // add offset to builderparts(min value of 0)
        $this->offset = max(0, (int) $offset);

        // return self
        return $this;
    }

    /**
     * function paginate.
     *
     * @param int $page
     * @param int $perPage
     *
     * @throws Exception
     *
     * @return array
     */
    public function paginate(int $currentPage, int $perPage = 15): array
    {
        // clone current query
        $clone = clone $this;

        // overwrite select columns
        $clone->columns = ['count(*) as total_results'];

        // fetch total_results count
        $totalResults = intval($clone->column(0));

        // return new instance of paginator
        return Paginator::make($this, $totalResults, $perPage, $currentPage)->toArray();
    }

    /**
     * function orderBy.
     *
     * @param string $column
     * @param string $direction
     *
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        // make direction to uppercase
        $direction = strtoupper($direction);

        // add orderBy to builder parts
        $this->orders[] = compact(
            'column',
            'direction'
        );

        // return self
        return $this;
    }

    /**
     * function groupBy.
     *
     * @param string|array $groups
     *
     * @return self
     */
    public function groupBy(string ...$groups): self
    {
        // loop through all groups
        $this->groups = array_merge($this->groups, flattenArray($groups));

        // return self
        return $this;
    }

    /**
     * @param bool     $when
     * @param callable $callback
     *
     * @return QueryBuilder
     */
    public function when(bool $when, callable $callback): self
    {
        // make closure from callback
        $callback = Closure::fromCallable($callback);

        // when
        if ($when) {
            // call callable
            $callback($builder = $this);

            // return new builder
            return $builder;
        }

        // return self
        return $this;
    }

    /**
     * This method will reset all class properties.
     *
     * @return void
     */
    public function reset(): void
    {
        // reset all settings
        foreach ($this->resetData as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * This method will return all results when you use queryBuilder without all, one, column inside a foreach.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all([]));
    }
}
