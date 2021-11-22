<?php

namespace Framework\Database;

class QueryBuilder
{
    use DatabaseHelpers;

    /**
     * function selectToSql
     * @param Database $query
     * @return array
     */

    protected function selectToSql(Database $query): array
    {
        // format select columns into string
        $select = implode(', ', $query->columns);

        // return select query
        return [
            "SELECT {$select} FROM `{$query->from}`" . $this->format($query),
            $query->flattenArray($this->bindings)
        ];
    }

    /**
     * function insertToSql
     * @param Database $query
     * @param array $insertData
     * @return array
     */

    protected function insertToSql(Database $query, array $insertData): array
    {
        // check if array is multidymensinal
        $isMultidymential = count($insertData) != count($insertData, COUNT_RECURSIVE);

        // keep track of data
        $bindData = [];

        // check if insertData is multidymential
        if ($isMultidymential) {
            // keep track of value placeholders
            $valuePlaceholders = '';

            // merge datainto one layer
            foreach ($insertData as $value) {
                // merge data into one layer
                $bindData = array_merge($bindData, array_values($value));

                // make for count(value) an ?
                $val = rtrim(str_repeat('?,', count($value)), ',');
                // make value placeholder
                $valuePlaceholders .= "({$val}),";
            }

            // trim last comma in string
            $valuePlaceholders = rtrim($valuePlaceholders, ',');
        } else {
            // get value placeholders
            $valuePlaceholders = rtrim(str_repeat('?,', count($insertData)), ',');
            // update data variable
            $bindData = $insertData;
        }

        // make string of column names
        $columns = implode('`,`', array_keys($isMultidymential ? $insertData[0] : $insertData));

        // trim ( AND ) from begin and end for when there are to much wrapping
        $valuePlaceholders = trim($valuePlaceholders, '()');

        // return insert query
        return [
            "INSERT INTO `{$query->from}` (`{$columns}`) VALUES ({$valuePlaceholders})",
            $query->flattenArray($insertData)
        ];
    }

    /**
     * function updateToSql
     * @param Database $query
     * @param array $updateData
     * @return array
     */

    protected function updateToSql(Database $query, array $updateData)
    {
        // keep track of bind data
        $bindData = array_values($updateData);

        // walk trough update data
        array_walk($updateData, function (&$value, $key) {
            // check if value is not an array and not an object
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            // update value to correct statement
            if (preg_match('/[A-Za-z]+\(\)|\-\=|\+\=/i', $value)) {
                $value = " `{$key}` = {$value}";
            } else {
                $value = " `{$key}` = ?";
            }
        });

        // get all update statement
        $setPlaceholders = implode(',', array_values($updateData));

        // return query and bindings
        return [
            "UPDATE `{$query->from}` SET {$setPlaceholders}" . $this->format($query),
            array_merge($bindData, $query->flattenArray($query->bindings))
        ];
    }

    /**
     * function deleteToSql
     * @param Database $query
     * @return array
     */

    protected function deleteToSql(Database $query)
    {
        // return query and bindings
        return [
            "DELETE FROM `{$query->from}`" . $this->format($query),
            $query->flattenArray($query->bindings)
        ];
    }

    /**
     * function format
     * @param Database $query
     * @return string
     */

    protected function format(Database $query): string
    {
        // get formatted joins
        $joins = $this->formatJoins($query->joins);

        // format where statement
        $whereClause = $this->formatWhere($query->wheres);

        // add WHERE to statement if not empty
        $whereClause = !empty($whereClause) ? " WHERE {$whereClause}" : '';

        // format group by
        $groupBy = !empty($query->groups) ? ' GROUP BY ' . $this->formatGroupBy($query->groups) : '';

        // format order by
        $orderBy = !empty($query->orders) ? ' ORDER BY ' . $this->formatOrderBy($query->orders) : '';

        // format limit
        $limit = isset($query->limit) ? " LIMIT {$query->limit}" : '';

        // format offset
        $offset = isset($query->offset) ? " OFFSET {$query->offset}" : '';

        // return query and bindData
        return $joins . $whereClause . $groupBy . $orderBy . $limit . $offset;
    }

    /**
     * function formatGroupBy
     * @param array $groups
     * @return string
     */

    private function formatGroupBy(array $groups): string
    {
        // loop trough all groups
        $groups = array_map(function ($group) {
            return preg_replace('/([A-z0-9_\-]+)/', '`$1`', $group);
        }, $groups);

        // return groupBy string
        return implode(', ', $groups);
    }

    /**
     * function formatOrderBy
     * @param array $orders
     * @return string
     */

    private function formatOrderBy(array $orders): string
    {
        // keep track of orders
        $orderBy = [];

        // loop trough all orders
        foreach ($orders as $order) {
            // add order to orderBy array
            $orderBy[] = "`{$order['column']}` {$order['direction']}";
        }

        // return string with comma as separator
        return implode(', ', $orderBy);
    }

    /**
     * function formatJoins
     * @param array $joins
     * @return string
     */

    public function formatJoins(array $joins): string
    {
        // keep track of statement
        $joinStatement = '';

        // loop trough all join classes
        foreach ($joins as $join) {
            // add statement to statement string
            $joinStatement .= " {$join->type} JOIN `{$join->table}` ON " . $this->formatWhere($join->wheres);
        }

        // return statement string
        return $joinStatement;
    }

    /**
     * function formatSubSelect
     * @param array $where
     * @return array
     */

    public function formatSubSelect(array $whereClause, array $where): array
    {
        // get where clause
        $_whereClause = $this->formatWhere($where);

        // format where clause
        if (!empty($whereClause)) {
            $whereClause[] = " {$where[0]['boolean']} ({$_whereClause}) ";
        } else {
            $whereClause[] = " ({$_whereClause}) ";
        }

        // return where statements
        return $whereClause;
    }

    /** 
     * function formatWhere
     * @param array $where
     * @return string
     */

    protected function formatWhere(array $where): string
    {
        // keep track of where clause
        $whereClause = [];

        // types that have an valid column value
        $typesWithColumnValue = [
            'column',
            'normal'
        ];

        // koop trough all where statements
        foreach ($where as $value) {
            // check if key is string (column name)
            if (isset($value[0])) {
                // merge sub select with whereclause
                $whereClause = array_merge($whereClause, $this->formatSubSelect($whereClause, $value));

                // go to the next on in the array
                continue;
            }

            // get type from where value
            $type = isset($value['type']) ? $value['type'] : 'normal';

            // default placeholder
            $placeholder = '?';

            // keep track of column name
            $column = $value['column'] ?? '';

            // check if type is raw
            if ($type === 'raw') {
                $whereClause[] = ($value['boolean'] ?? '') . ' ' . $value['query'];
            } elseif ($type === 'exists' || $type === 'notExists') {
                $whereClause[] = ($type === 'exists' ? 'exists' : 'not exists') . " ({$value['query']})";
            } elseif ($type === 'nested') {
                $whereClause[] = '(' . trim($value['query']) . ')';
            } elseif ($type === 'column') {
                $placeholder = $this->formatColumnNames($value['value']);
            }

            // check if can continue
            if (!in_array($type, $typesWithColumnValue)) {
                continue;
            }

            // make column format
            if (!empty($column) && !strpos($column, '.')) {
                $column = '  ' . $this->from . '.' . $column;
            }

            // format columns
            $column = $this->formatColumnNames($column);

            // add to where clause
            $whereClause[] = strtoupper($value['boolean'] ?? '') . $column . ' ' . ($value['operator'] ?? '') . ' ' . $placeholder;
        }

        // return where clause
        return trim(implode(' ', $whereClause), ' AND OR');
    }
}