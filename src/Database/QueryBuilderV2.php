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
        [$where,$bindData] = $this->formatWhere($query->bindings['where']);

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
                $whereClause = ' ' . $value['column'].' ' . $value['operator'] . ' ' . $placeholder;
            } else {
                $whereClause .=  ' ' . strtoupper($value['boolean']) . ' ' . $value['column'].' ' . $value['operator'] . ' ' . $placeholder;
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
