<?php

namespace Framework\Database;

use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Model\BaseModel;

trait DatabaseHelpers
{
    /**
     * @param string $columnName
     *
     * @return string
     */
    public function formatColumnNames(string $columnName): string
    {
        if (strpos($columnName, '.') !== false) {
            return preg_replace('/^([A-z0-9_\-]+)\.([A-z0-9_\-]+)$/', '`$1`.`$2`', $columnName);
        } else {
            return preg_replace('/^([A-z0-9_\-]+)$/', '`$1`', $columnName);
        }
    }

    /**
     * @param mixed $selectColumn
     *
     * @return array
     */
    protected function selectFormat(mixed $selectColumn): array
    {
        // keep track of select columns
        $selectColumns = [];

        // check if select columns is an array
        if (is_array($selectColumn)) {
            // loop trough all select columns
            foreach ($selectColumn as $column) {
                // merge select columns
                $selectColumns = array_merge($selectColumns, $this->selectFormat($column));
            }
        } else {
            $selectColumns[] = $selectColumn;
        }

        // return selectColumns
        return $selectColumns;
    }

    /**
     * @param QueryBuilder $mainQuery
     * @param QueryBuilder $mergeQuery
     *
     * @return void
     */
    public function mergeBindings(QueryBuilder $mainQuery, QueryBuilder $mergeQuery): void
    {
        // loop through all bindings
        foreach ($mergeQuery->bindings as $key => $binding) {
            // merge binding
            $mainQuery->bindings[$key] = array_merge($mainQuery->bindings[$key], $binding);
        }
    }

    /**
     * @param Collection|BaseModel|array|bool $result
     * 
     * @return Collection|BaseModel|array|bool
     */
    protected function mergeRelations($result)
    {
        if (empty($relations) || is_bool($result) || is_array($result)) return $result;

        foreach ($this->withRelations as $relation) {
            $result = $relation->mergeRelation(
                $result,
                $relation->getData($result instanceof BaseModel ? collection([$result])->all() : $result->all())
            );
        }

        return $result;
    }
}
