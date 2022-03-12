<?php

declare(strict_types=1);

namespace Framework\Model\Relation\RelationTypes;

use Closure;
use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\Paginator\Paginator;
use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Model\BaseModel;
use Framework\Model\Relation\BaseRelation;

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
class HasMany extends BaseRelation
{
    /**
     * @param class-string $relation
     * @param BaseModel    $fromModel
     * @param string|null  $foreignKey
     * @param string|null  $primaryKey
     * @param Closure|null $query
     */
    public function __construct(
        public string $relation,
        protected BaseModel $fromModel,
        public ?string $foreignKey = null,
        public ?string $primaryKey = null,
        public ?Closure $query = null
    ) {
        $this->primaryKey = $this->primaryKey ?: $this->getFromModel()->getPrimaryKey();
        $this->foreignKey = $this->foreignKey ?: $this->getForeignKeyByModel($this->getFromModel());
    }

    /**
     * @param Collection|BaseModel $result
     *
     * @return Collection|Paginator
     */
    public function getData(Collection|BaseModel $result): Collection|Paginator
    {
        $query = $this->buildQuery(
            $this->foreignKey,
            $result instanceof Collection ? $result->column($this->primaryKey)->all() : [$result->getOriginal()->{$this->primaryKey}]
        );

        $query = $this->query ? ($this->query)($query) : $query;

        return $query instanceof QueryBuilder ? $query->all() : $query;
    }

    /**
     * @template TValue
     * 
     * @param  TValue $baseData
     * @param  BaseModel|Collection $relationData
     * 
     * @return TValue
     */
    public function mergeRelation($baseData, $relationData)
    {
        if ($baseData instanceof BaseModel) {
            $baseData->{$this->getName()} = $relationData;

            return $baseData;
        }

        if ($baseData instanceof Collection) {
            return $baseData->each(function (&$item) use ($relationData) {
                $item->{$this->getName()} = $relationData->filter(fn ($value) => $value->{$this->foreignKey} === $item->{$item->getPrimaryKey()});
            });
        }

        return $baseData;
    }
}
