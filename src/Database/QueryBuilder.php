<?php

namespace Framework\Database;

class QueryBuilder
{
    protected function toSql(Database $database): string
    {
        if ($database->queryType === 'SELECT') {
            return $this->selectToSql($database);
        } elseif ($database->queryType === 'DELETE') {
            return $this->deleteToSql($database);
        } elseif ($database->queryType === 'UPDATE') {
            return $this->updateToSql($database);
        } elseif ($database->queryType === 'INSERT') {
            return $this->insertToSql($database);
        }
    }

    protected function selectToSql(Database $database): string
    {
        return "SELECT {$database->select} FROM `{$database->tableName}`".
        $database->joinQuerys.
        $this->formatWhere($database).
        $database->orderBy.
        $database->groupBy.
        $database->limit;
    }

    protected function deleteToSql(Database $database): string
    {
        return "DELETE FROM `{$database->tableName}`".
        $database->joinQuerys.
        $this->formatWhere($database).
        $database->orderBy.
        $database->groupBy.
        $database->limit;
    }

    protected function updateToSql(Database $database): string
    {
        return "UPDATE `{$database->tableName}`".
        $this->formatSet($database).
        $database->joinQuerys.
        $this->formatWhere($database).
        $database->orderBy.
        $database->groupBy.
        $database->limit;
    }

    protected function insertToSql(Database $database): string
    {
        $keys = array_keys($database->data);

        $bindingKeys = implode(',', array_map(
            function ($value, $key) {
                return sprintf(":%s", $key);
            },
            $database->whereData,
            $keys
        ));

        // maak keys to string
        $keys = implode('`,`', $keys);

        return "INSERT INTO `{$database->tableName}` (`{$keys}`) VALUES ({$bindingKeys})";
    }

    private function formatSet(Database $database): string
    {
        if (empty($database->data)) {
            throw new \Exception("Je moet een nieuwe waarde invullen", 1);
            return '';
        }

        $setString = '';

        // ga door alle update data
        foreach ($database->data as $key => $value) {
            // voeg seperators toe
            if (empty($setString)) {
                $setString = ' SET `' . $key . '` = :'.$key.', ';
            } elseif (array_key_last($database->data) != $key) {
                $setString .= '`' . $key . '` = :'.$key.', ';
            } else {
                $setString .= '`' . $key . '` = :'.$key;
            }
        }

        return trim($setString, ', ');
    }

    private function formatWhere(Database $database): string
    {
        if (empty($database->wheres)) {
            return '';
        }

        $wheres = $database->wheres;
        $seperators = $database->seperators;

        $whereQuery = ' WHERE ';

        foreach ($wheres as $key => $where) {
            // voeg query toe
            $whereQuery .= $where;

            // voeg seperators toe
            if (isset($seperators[$key]) && array_key_last($wheres) != $key) {
                $whereQuery .= ' ' . $seperators[$key] . ' ';
            } elseif (array_key_last($wheres) != $key) {
                $whereQuery .= ' AND ';
            }
        }

        return $whereQuery;
    }
}
