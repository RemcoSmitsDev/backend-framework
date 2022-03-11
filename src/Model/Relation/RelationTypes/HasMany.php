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
class HasMany extends BaseRelation
{
    /**
     * @param string       $relation
     * @param BaseModel    $fromModel
     * @param string       $foreignKey
     * @param string|null  $primaryKey
     * @param Closure|null $query
     */
    public function __construct(
        public string $relation,
        protected BaseModel $fromModel,
        public string $foreignKey,
        public ?string $primaryKey = null,
        public ?Closure $query = null
    ) {
    }

    /**
     * @param array $results
     *
     * @return Collection|Paginator
     */
    public function getData(array $results = []): Collection|Paginator
    {
        $fromModel = $this->getFromModel();

        $query = $this->buildWhereInQuery(
            $this->getHasManyRelation($fromModel)->foreignKey,
            [$fromModel->getOriginal()->{$this->primaryKey}]
        );

        $query = $this->query ? ($this->query)($query) : $query;

        if ($query instanceof QueryBuilder) {
            return $query->all();
        }

        return $query;
    }

    /**
     * @param BaseModel $fromModel
     *
     * @return BaseRelation
     */
    private function getHasManyRelation(BaseModel $fromModel): BaseRelation
    {
        $hasMany = collection($this->getRelationInstance()->initRelations()->getRelations())
            ->filter(fn ($relation) => $relation->relation === $fromModel::class)
            ->first();

        if (!$hasMany) {
            throw new Exception('There was no relation found for ['.$fromModel::class.']!');
        }

        return $hasMany;
    }

    public function mergeRelation($baseData, $relationData): BaseModel|Collection|Paginator
    {
        return $baseData;
    }
}
