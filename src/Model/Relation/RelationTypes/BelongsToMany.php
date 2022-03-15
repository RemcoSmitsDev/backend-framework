<?php

declare(strict_types=1);

namespace Framework\Model\Relation\RelationTypes;

use Closure;
use Exception;
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
class BelongsToMany extends BaseRelation
{
    /**
     * @param class-string $relation
     * @param BaseRelation $fromModel
     * @param string       $table
     * @param string|null  $foreignKey
     * @param string|null  $relationForeignKey
     * @param Closure|null $query
     */
    public function __construct(
        public string $relation,
        protected BaseModel $fromModel,
        public string $table,
        public ?string $foreignKey = null,
        public ?string $relationForeignKey = null,
        public ?Closure $query = null
    ) {
    }

    /**
     * @param Collection|BaseModel $result
     *
     * @return Collection|Paginator
     */
    public function getData(Collection|BaseModel $result): Collection|Paginator
    {
        // when there where no ids found
        if (empty($result)) {
            return collection([]);
        }

        $fromModel = $this->getFromModel();

        // get belongs to many relation class
        $belongsToMany = $this->getBelongsToManyRelation($fromModel);
        $baseModelInstance = new $belongsToMany->relation();

        // get the foreignKeys
        $relationForeignKey = $this->relationForeignKey ?: $this->getForeignKeyByModel($baseModelInstance);
        $baseModelForeignKey = $this->foreignKey ?: $this->getForeignKeyByModel($belongsToMany->getFromModel());

        // build query
        $query = $baseModelInstance->select(
            $baseModelInstance->getTable() . '.*',
            $this->table . '.' . $baseModelForeignKey . ' as pivot_' . $baseModelForeignKey,
            $this->table . '.' . $relationForeignKey . ' as pivot_' . $relationForeignKey
        )
            ->join(
                $belongsToMany->table,
                $baseModelInstance->getTable() . '.' . $baseModelInstance->getPrimaryKey(),
                '=',
                $belongsToMany->table . '.' . $relationForeignKey
            )
            ->whereIn(
                $belongsToMany->table . '.' . $baseModelForeignKey,
                $result instanceof BaseModel ? [$result->getOriginal()->{$fromModel->getPrimaryKey()}] : $result->column($fromModel->getPrimaryKey())->all()
            );

        $query = $this->query ? ($this->query)($query) : $query;

        return $query instanceof QueryBuilder ? $query->all() : $query;
    }

    /**
     * @param  BaseModel
     *
     * @return BaseRelation
     */
    private function getBelongsToManyRelation(BaseModel $fromModel): BaseRelation
    {
        $belongsToMany = collection($fromModel->getRelations())->filter(fn ($relation) => $this->relation === $relation->relation)->first();

        if (!$belongsToMany) {
            throw new Exception('There was no relation found for [' . $fromModel::class . ']!');
        }

        return $belongsToMany;
    }

    /**
     * @template TValue
     *
     * @param TValue                         $baseData
     * @param BaseModel|Collection|Paginator $relationData
     *
     * @return TValue
     */
    public function mergeRelation($baseData, $relationData): BaseModel|Collection
    {
        if ($baseData instanceof Collection) {
            return $baseData->each(function (&$item) use ($relationData) {
                $item->{$this->getName()} = $relationData instanceof BaseModel ? $relationData : $relationData->filter(fn ($value) => $value->{'pivot_' . $this->getForeignKeyByModel($this->getFromModel())} === $item->{$item->getPrimaryKey()});
            });
        }

        if ($baseData instanceof BaseModel) {
            $baseData->{$this->getName()} = $relationData;
        }

        return $baseData;
    }
}
