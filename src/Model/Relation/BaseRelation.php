<?php

declare(strict_types=1);

namespace Framework\Model\Relation;

use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\Paginator\Paginator;
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
abstract class BaseRelation
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var BaseModel|null
     */
    protected ?BaseModel $instance = null;

    /**
     * @var array
     */
    public $nestedRelations = [];

    /**
     * @param BaseModel $model
     *
     * @return string
     */
    final public function getForeignKeyByModel(BaseModel $model): string
    {
        return preg_replace('/ie$/', 'y', rtrim($model->getTable(), 's')).'_'.$model->getPrimaryKey();
    }

    /**
     * @param string $name
     *
     * @return self
     */
    final public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    final public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param BaseModel $fromModel
     *
     * @return self
     */
    final public function setFromModel(BaseModel $fromModel): self
    {
        $this->fromModel = $fromModel;

        return $this;
    }

    /**
     * @return BaseModel
     */
    final public function getFromModel()
    {
        return $this->fromModel;
    }

    /**
     * @return BaseModel
     */
    protected function getRelationInstance(): BaseModel
    {
        return $this->instance ??= new $this->relation();
    }

    /**
     * @param array $relations
     *
     * @return self
     */
    public function setNestedRelations(array $relations): self
    {
        $this->nestedRelations = $relations;

        return $this;
    }

    /**
     * @return array
     */
    public function getNestedRelations(): array
    {
        return $this->nestedRelations;
    }

    /**
     * @param string $key
     * @param array  $values
     *
     * @return QueryBuilder
     */
    final public function buildQuery(string $key, array $values): QueryBuilder
    {
        return $this->getRelationInstance()->query()->{count($values) > 1 ? 'whereIn' : 'where'}($this->getRelationInstance()->getTable().'.'.$key, count($values) > 1 ? $values : $values[0]);
    }

    /**
     * @param Collection|BaseModel $result
     *
     * @return BaseModel|Collection|Paginator
     */
    abstract public function getData(Collection|BaseModel $result): BaseModel|Collection|Paginator;

    /**
     * @template TValue
     *
     * @param TValue    $baseData
     * @param Paginator $relationData
     *
     * @return TValue
     */
    abstract public function mergeRelation(BaseModel|Collection $baseData, BaseModel|Collection|Paginator $relationData);
}
