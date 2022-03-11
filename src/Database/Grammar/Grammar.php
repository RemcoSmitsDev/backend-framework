<?php

declare(strict_types=1);

namespace Framework\Database\Grammar;

use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Database\QueryBuilder\SubQuery\SubQuery;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations, 
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).  
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class Grammar
{
    /**
     * function selectToSql.
     *
     * @param QueryBuilder $query
     *
     * @return array
     */
    public function selectToSql(QueryBuilder $query): array
    {
        if($query->isRaw) return $query->rawQuery;
        
        // format select columns into string
        $select = implode(', ', $query->columns ?: ['*']);

        // return select query
        return [
            "SELECT {$select} FROM `{$query->from}`".$this->format($query),
            flattenArray($query->bindings),
        ];
    }

    /**
     * function insertToSql.
     *
     * @param QueryBuilder $builder
     * @param array        $insertData
     *
     * @return array
     */
    public function insertToSql(QueryBuilder $builder, array $insertData): array
    {
        // check if array is multidimensional
        $isMultidimensional = count($insertData) != count($insertData, COUNT_RECURSIVE);

        // keep track of data
        $bindData = [];

        // check if insertData is multidimensional
        if ($isMultidimensional) {
            // keep track of value placeholders
            $valuePlaceholders = '';

            // merge data into one layer
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
        $columns = implode('`,`', array_keys($isMultidimensional ? $insertData[0] : $insertData));

        // trim ( AND ) from begin and end for when there are to much wrapping
        $valuePlaceholders = trim($valuePlaceholders, '()');

        // return insert query
        return [
            "INSERT INTO `{$builder->from}` (`{$columns}`) VALUES ({$valuePlaceholders})",
            flattenArray($insertData),
        ];
    }

    /**
     * function updateToSql.
     *
     * @param QueryBuilder $builder
     * @param array        $updateData
     *
     * @return array
     */
    public function updateToSql(QueryBuilder $builder, array $updateData): array
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
            if (is_string($value) && preg_match('/[A-Za-z]+\(\)|\-\=|\+\=/i', $value)) {
                $value = " `{$key}` = {$value}";
            } else {
                $value = " `{$key}` = ?";
            }
        });

        // get all update statement
        $setPlaceholders = implode(',', array_values($updateData));

        // return query and bindings
        return [
            "UPDATE `{$builder->from}` SET {$setPlaceholders}".$this->format($builder),
            array_merge($bindData, flattenArray($builder->bindings)),
        ];
    }

    /**
     * function deleteToSql.
     *
     * @param QueryBuilder $builder
     *
     * @return array
     */
    public function deleteToSql(QueryBuilder $builder): array
    {
        // return query and bindings
        return [
            "DELETE FROM `{$builder->from}`".$this->format($builder),
            flattenArray($builder->bindings),
        ];
    }

    /**
     * function format.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    protected function format(QueryBuilder $builder): string
    {
        // get formatted joins
        $joins = $this->formatJoins($builder);

        // format where statement
        $whereClause = $this->formatWhere($builder, $builder->wheres);

        // add WHERE to statement if not empty
        $whereClause = !empty($whereClause) ? " WHERE {$whereClause}" : '';

        // format group by
        $groupBy = !empty($builder->groups) ? ' GROUP BY '.$this->formatGroupBy($builder->groups) : '';

        // format order by
        $orderBy = !empty($builder->orders) ? ' ORDER BY '.$this->formatOrderBy($builder->orders) : '';

        // format limit
        $limit = isset($builder->limit) ? " LIMIT {$builder->limit}" : '';

        // format offset
        $offset = isset($builder->offset) ? " OFFSET {$builder->offset}" : '';

        // return query and bindData
        return $joins.$whereClause.$groupBy.$orderBy.$limit.$offset;
    }

    /**
     * function formatGroupBy.
     *
     * @param array $groups
     *
     * @return string
     */
    private function formatGroupBy(array $groups): string
    {
        // loop through all groups
        $groups = array_map(function ($group) {
            return preg_replace('/([A-z0-9_\-]+)/', '`$1`', $group);
        }, $groups);

        // return groupBy string
        return implode(', ', $groups);
    }

    /**
     * function formatOrderBy.
     *
     * @param array $orders
     *
     * @return string
     */
    private function formatOrderBy(array $orders): string
    {
        // keep track of orders
        $orderBy = [];

        // loop through all orders
        foreach ($orders as $order) {
            // add order to orderBy array
            $orderBy[] = preg_replace('/([A-z0-9_\-]+)/', '`$1`', $order['column']).' '.$order['direction'];
        }

        // return string with comma as separator
        return implode(', ', $orderBy);
    }

    /**
     * function formatJoins.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    public function formatJoins(QueryBuilder $builder): string
    {
        // keep track of statement
        $joinStatement = '';

        // loop through all join classes
        foreach ($builder->joins as $join) {
            // add statement to statement string
            $joinStatement .= " {$join->type} JOIN `{$join->table}` ON ".$this->formatWhere($builder, $join->wheres);
        }

        // return statement string
        return $joinStatement;
    }

    /**
     * function formatSubSelect.
     *
     * @param QueryBuilder $builder
     * @param array        $whereClause
     * @param array        $where
     *
     * @return array
     */
    public function formatSubSelect(QueryBuilder $builder, array $whereClause, array $where): array
    {
        // get where clause
        $_whereClause = $this->formatWhere($builder, $where);

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
     * function formatWhere.
     *
     * @param QueryBuilder $builder
     * @param array        $where
     *
     * @return string
     */
    public function formatWhere(QueryBuilder $builder, array $wherestatements): string
    {
        // keep track of where clause
        $whereClause = [];

        // types that have an valid column value
        $typesWithColumnValue = [
            'column',
            'normal',
            'notExists',
            'exists',
        ];

        // loop through all where statements
        foreach ($wherestatements as $wherestatement) {
            // check if is instance of sub query
            if ($wherestatement instanceof SubQuery) {
                // append to where clause
                $whereClause[] = $wherestatement->boolean.' '.(string) $wherestatement;

                // continue to next where statement
                continue;
            }

            // get type from where value
            $type = $wherestatement['type'] ?? 'normal';

            // default placeholder
            $placeholder = '?';

            // keep track of column name
            $column = $wherestatement['column'] ?? '';

            // check if type is raw
            if ($type === 'raw') {
                $whereClause[] = ($wherestatement['boolean'] ?? '').' '.$wherestatement['query'];
            } elseif ($type === 'column') {
                $placeholder = $builder->formatColumnNames($wherestatement['value']);
            } elseif ($type === 'normal') {
                $wherestatement['boolean'] .= ' ';
            }

            // check if can continue
            if (!in_array($type, $typesWithColumnValue)) {
                continue;
            }

            // make column format
            if (!empty($column) && !strpos($column, '.')) {
                $column = $builder->formatColumnNames($builder->from.'.'.$column);
            } else {
                // format columns
                $column = $builder->formatColumnNames($column);
            }

            // add to where clause
            $whereClause[] = strtoupper($wherestatement['boolean'] ?? '').$column.' '.($wherestatement['operator'] ?? '').' '.$placeholder;
        }

        // return where clause
        return trim(implode(' ', $whereClause), ' AND OR');
    }
}
