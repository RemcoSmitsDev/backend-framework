<?php

namespace Framework\Database;

trait QueryBuilderV2
{
    private function selectToSql(DatabaseV2 $query): array
    {
        // format select columns into string
        $select = implode(', ', $query->bindings['select']);

        // get from table
        $from = $query->bindings['from'];

        // format where statement
        [$where, $bindData] = $this->formatWhere($query->bindings['where']);

        // when where statement is not empty and `WHERE` is not in string
        if (!empty($where)) {
            $where = " WHERE {$where}";
        }

        // return select query
        return [
            "SELECT {$select} FROM `{$from}`{$where}",
            $bindData
        ];
    }

    public function insertToSql(DatabaseV2 $query, array $insertData): array
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
            "INSERT INTO `{$query->bindings['from']}` (`{$columns}`) VALUES ({$valuePlaceholders})",
            $bindData
        ];
    }

    public function formatWhere(array $where): array
    {
        // keep track of where clause
        $whereClause = '';
        $bindData = [];

        // koop trough all where statements
        foreach ($where as $key => $value) {
            // check if key is string (column name)
            if (isset($value[0])) {
                // get where clause
                [$_whereClause, $_bindData] = $this->formatWhere($value);

                // merge bindData
                $bindData = array_merge($bindData, $_bindData);

                // format where clause
                if (!empty($whereClause)) {
                    $whereClause .= " {$value[0]['boolean']} ({$_whereClause}) ";
                } else {
                    $whereClause .= " ({$_whereClause}) ";
                }
                // go to the next on in the array
                continue;
            }

            // get type from where value
            $type = isset($value['type']) ? $value['type'] : 'normal';

            // default placeholder
            $placeholder = '?';

            // check if type is raw
            if ($type === 'raw') {
                // make placeholder the raw query
                $placeholder = $value['value'];
            }

            // check if whereClause is empty
            if (empty($whereClause)) {
                $whereClause = ' ' . $value['column'] . ' ' . $value['operator'] . ' ' . $placeholder;
            } else {
                $whereClause .=  ' ' . strtoupper($value['boolean']) . ' ' . $value['column'] . ' ' . $value['operator'] . ' ' . $placeholder;
            }

            // check if type is raw
            if ($type !== 'raw') {
                // add data to bindData
                $bindData[] = $value['value'];
            }
        }

        // return where clause
        return [
            $whereClause,
            $bindData
        ];
    }
}
