<?php

namespace Framework\Database;

class Database extends QueryBuilder
{
    // connection settings
    private string $host;
    private string $user;
    private string $pass;
    private string $DBName;

    // pdo vars
    private ?object $PDO = null;
    private object $stmt;

    private array $fetchTypes = [
      'all' => [
        'name' => 'fetchAll',
        'arg' => \PDO::FETCH_OBJ
      ],
      'one' => [
        'name' => 'fetch',
        'arg' => \PDO::FETCH_OBJ
      ],
      'column' => [
        'name' => 'fetch',
        'arg' => \PDO::FETCH_OBJ
      ],
    ];

    // json(all,single) function
    protected bool $json = false;

    protected array $whereData = [];
    protected array $wheres = [];
    protected array $seperators = [];
    protected array $data = [];

    protected string $tableName;
    protected string $select = '*';

    protected string $queryType = 'SELECT';
    protected bool $isRaw = false;


    protected string $joinQuerys = '';

    protected $limit = '';
    protected string $orderBy = '';
    protected string $groupBy = '';

    // dump query
    private string $SQLQuery;
    private array $skipToDump = ['host','user','pass','DBName','PDO','stmt','fetchTypes','skipToDump','dump'];
    private object $dump;

    public function __construct()
    {
        // define database dev/live config
        $this->host = constant(config()::class.'::'.config()::DB_CONNECTION_PREFIX.'HOST');
        $this->user = constant(config()::class.'::'.config()::DB_CONNECTION_PREFIX.'USER');
        $this->pass = constant(config()::class.'::'.config()::DB_CONNECTION_PREFIX.'PASS');
        $this->DBName = constant(config()::class.'::'.config()::DB_CONNECTION_PREFIX.'NAME');
    }

    private function connect(): self
    {
        if (!is_null($this->PDO)) {
            return $this;
        }

        $PDOSettings = "mysql:host={$this->host};dbname={$this->DBName};port=8889";

        $options = array(
          \PDO::ATTR_PERSISTENT => true,
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
          \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        );

        try {
            $this->PDO = new \PDO($PDOSettings, $this->user, $this->pass, $options);
        } catch (\PDOExeption $e) {
            echo $e->getMessage();
        }

        return $this;
    }

    private function query(string $query): self
    {
        $this->stmt = $this->connect()->PDO->prepare($this->SQLQuery = $query);
        return $this;
    }

    public function raw(string $rawQuery)
    {
        $this->isRaw = true;
        return $this->connect()->query($rawQuery);
    }

    public function all($defaultReturn = false, int $fetchType = null)
    {
        if (!is_null($fetchType)) {
            $this->tempFetchType = $fetchType;
        }
        return $this->handleReturnData($defaultReturn, 'all');
    }

    public function one($defaultReturn = false, int $fetchType = null)
    {
        if (!is_null($fetchType)) {
            $this->tempFetchType = $fetchType;
        }
        return $this->handleReturnData($defaultReturn, 'one');
    }

    public function column($defaultReturn = false, int $fetchType = null)
    {
        if (!is_null($fetchType)) {
            $this->tempFetchType = $fetchType;
        }
        return $this->handleReturnData($defaultReturn, 'column');
    }

    public function insert(array $insertData)
    {
        $this->queryType = 'INSERT';
        $this->data = $insertData;

        $this->query($this->toSql($this))
               ->autoBind($this->whereData)
               ->autoBind($insertData)
               ->execute()
               ->clearPreviousData()
               ->close();

        return $this;
    }

    public function delete()
    {
        $this->queryType = 'DELETE';

        $this->query($this->toSql($this))
                 ->autoBind($this->whereData)
                 ->execute()
                 ->clearPreviousData()
                 ->close();

        return $this;
    }

    public function update(array $updateData = [])
    {
        $this->queryType = 'UPDATE';
        $this->data = $updateData;

        if ($this->isRaw) {
            $this->autoBind($this->whereData)
          ->autoBind($updateData)
          ->execute()
          ->clearPreviousData()
          ->close();

            return $this;
        }

        $this->query($this->toSql($this))
                 ->autoBind($this->whereData)
                 ->autoBind($updateData)
                 ->execute()
                 ->clearPreviousData()
                 ->close();

        return $this;
    }

    private function handleReturnData($defaultReturn, $fetchType)
    {
        if (!$this->isRaw) {
            $this->query($this->toSql($this));
        }

        $this->autoBind($this->whereData);

        if ($this->count() === 0) {
            $this->clearPreviousData();
            return $defaultReturn;
        }

        $type = $this->fetchTypes[$fetchType]['name'];

        if ($this->json) {
            $this->clearPreviousData();
            return json_encode($this->stmt->$type($this->fetchTypes[$this->tempFetchType ?? $fetchType]['arg']), JSON_INVALID_UTF8_IGNORE);
        }

        $this->clearPreviousData();
        return $this->stmt->$type($this->fetchTypes[$this->tempFetchType ?? $fetchType]['arg']);
    }

    private function close(): self
    {
        $this->PDO = null;
        return $this;
    }

    private function replaceBindData(string &$SQLQuery, array $bindData): void
    {
        foreach ($bindData as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $val = preg_quote(clearInjections($val));

                    if (!is_numeric($val) && !is_null($val) && !is_bool($val)) {
                        $val = "'{$val}'";
                    }

                    $SQLQuery = preg_replace('/\:'.$key.'/', $val, $SQLQuery, 1);
                }
            } else {
                $value = addslashes(clearInjections($value));

                if (!is_numeric($value) && !is_null($value) && !is_bool($value)) {
                    $value = "'{$value}'";
                }

                $SQLQuery = preg_replace('/\:'.$key.'/', $value, $SQLQuery, 1);
            }
        }
    }

    public function dump(bool $showDump = true): ?object
    {
        $this->dump->SQLQueryWithValues = $this->dump->SQLQuery;

        $this->replaceBindData($this->dump->SQLQueryWithValues, $this->dump->whereData);
        $this->replaceBindData($this->dump->SQLQueryWithValues, $this->dump->data);

        if ($showDump) {
            echo '<pre>';
            var_dump($this->dump);
            echo '</pre>';
        }
        return $this;
    }

    private function execute(): self
    {
        $this->stmt->execute();
        $this->close();
        return $this;
    }

    public function count()
    {
        $this->execute();
        return $this->stmt->rowCount();
    }

    public function json()
    {
        $this->json = true;
        return $this;
    }

    private function clearPreviousData()
    {
        $this->dump = new \stdClass();

        foreach (get_object_vars($this) as $key => $value) {
            if (in_array($key, $this->skipToDump)) {
                continue;
            }
            $this->dump->$key = $value;
        }

        $this->json = false;

        $this->whereData = [];
        $this->wheres = [];
        $this->seperators = [];
        $this->data = [];

        $this->tableName = '';
        $this->select = '*';

        $this->queryType = 'SELECT';
        $this->orderBy = '';
        $this->groupBy = '';
        $this->limit = '';
        $this->isRaw = false;

        $this->SQLQuery = '';
        $this->joinQuerys = '';

        if (isset($this->tempFetchType)) {
            $this->tempFetchType = false;
        }

        return $this;
    }

    public function table(string $tableName, string $select = '*')
    {
        $this->tableName = clearInjections($tableName);
        $this->select = clearInjections($select);
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = " LIMIT {$limit} ";
        return $this;
    }

    public function orderBy(string $orderBy)
    {
        $this->orderBy = ' ORDER BY '.clearInjections($orderBy);
        return $this;
    }

    public function groupBy(string $groupBy)
    {
        $this->groupBy = ' GROUP BY '.clearInjections($groupBy);
        return $this;
    }

    protected function findOrFailCheck($columnNames, $operators, $findValues)
    {
        if (empty($columnNames)) {
            return false;
        }


        foreach ($columnNames as $key => $columnName) {
            $operator = $operators[$key] ?? '=';

            $columnNames[$key] = str_replace('.', '_', $columnName);

            $this->wheres[] = "{$columnName} {$operator} :{$columnNames[$key]}";
        }

        $this->whereData[] = array_combine($columnNames, $findValues);

        return true;
    }

    protected function autoBind(array $bindData)
    {
        foreach ($bindData as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $this->bind(':'.$key, $val);
                }
            } else {
                $this->bind(':'.$key, $value);
            }
        }
        return $this;
    }

    public function or(): self
    {
        if (!empty($this->wheres)) {
            $this->seperators[] = 'OR';
        }
        return $this;
    }

    public function and(): self
    {
        if (!empty($this->wheres)) {
            $this->seperators[] = 'AND';
        }
        return $this;
    }

    public function join(string $tableName, \Closure $callback)
    {
        $on = $callback($this);

        $this->joinQuerys .= ' INNER JOIN ' . $tableName . $on;

        return $this;
    }

    public function leftJoin(string $tableName, \Closure $callback)
    {
        $on = $callback($this);

        $this->joinQuerys .= ' LEFT JOIN ' . $tableName . $on;

        return $this;
    }

    public function rightJoin(string $tableName, \Closure $callback)
    {
        $on = $callback($this);

        $this->joinQuerys .= ' RIGHT JOIN ' . $tableName . $on;

        return $this;
    }

    public function on(string $on1, string $on2): string
    {
        return ' ON ' . $on1 . ' = ' . $on2;
    }

    public function rawBindData(array $bindData)
    {
        $this->whereData = [(array)$bindData];
        return $this;
    }

    public function where(): self
    {
        $args = func_get_args();

        $columnNames = [];
        $operators = ['='];
        $findValues = [];

        if (count($args) == 2) {
            $columnNames = (array)$args[0];
            $findValues = (array)$args[1];
        } elseif (count($args) == 3) {
            $columnNames = (array)$args[0];
            $operators = (array)$args[1];
            $findValues = (array)$args[2];
        } else {
            throw new \Exception('Je moet 2 of 3 velden invullen', 1);
            return $this;
        }

        foreach ($args as $key => $value) {
            if (next($args) && count(is_array($args[$key+1]) ? $args[$key+1] : [$args[$key+1]]) != count(is_array($value) ? $value : [$value])) {
                throw new \Exception('Alle velden moeten evenlang zijn', 1);
                return $this;
            }
        }

        if (!$this->findOrFailCheck($columnNames, $operators, $findValues)) {
            return $this;
        }

        return $this;
    }

    public function whereRaw(string $whereClause)
    {
        $this->wheres[] = clearInjections($whereClause);
        return $this;
    }

    protected function bind($param, $value, $type = null)
    {
        $value = clearInjections($value);
        $param = clearInjections($param);

        $param = str_replace('.', '_', $param);

        if (is_null($type)) {
            switch ($value) {
            case is_int($value):
              $type = \PDO::PARAM_INT;
              break;
            case is_bool($value):
              $type = \PDO::PARAM_BOOL;
              break;
            case is_null($value):
              $type = \PDO::PARAM_NULL;
              break;
            default:
              $type = \PDO::PARAM_STR;
              break;
          }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
}
