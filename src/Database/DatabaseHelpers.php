<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Model\BaseModel;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
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
     * @template TValue
     *
     * @param TValue $result
     *
     * @return TValue
     */
    protected function mergeRelations($result, $relations = [])
    {
        if (empty($this->withRelations) && !empty($relations) || !$result instanceof BaseModel && !$result instanceof Collection) {
            return $result;
        }

        foreach ($relations ?: $this->withRelations as $relation) {
            $result = $relation->mergeRelation(
                $result,
                $relation->getNestedRelations() ?
                    $this->mergeRelations($relation->getData($result), $relation->getNestedRelations()) :
                    $relation->getData($result)
            );
        }

        return $result;
    }
}
