<?php

declare(strict_types=1);

namespace Framework\Model\Relation;

use Closure;
use Exception;
use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\Paginator\Paginator;
use Framework\Model\BaseModel;
use Framework\Model\Relation\RelationTypes\BelongsTo;
use Framework\Model\Relation\RelationTypes\BelongsToMany;
use Framework\Model\Relation\RelationTypes\HasMany;
use ReflectionClass;
use ReflectionMethod;

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
trait HasRelations
{
    /**
     * @var array<int, BaseRelation>
     */
    private array $relations = [];

    /**
     * @var bool
     */
    private bool $wasInitialized = false;

    /**
     * @param string       $relation
     * @param string       $foreignKey
     * @param string|null  $primaryKey
     * @param Closure|null $query
     *
     * @return BelongsTo
     */
    protected function belongsTo(
        string $relation,
        string $foreignKey,
        ?string $primaryKey = null,
        ?Closure $query = null
    ): BelongsTo {
        return new BelongsTo($relation, $this, $foreignKey, $primaryKey ?: $this->getPrimaryKey(), $query);
    }

    /**
     * @param string       $relation
     * @param string       $table
     * @param string|null  $foreignKey
     * @param string|null  $relationForeignKey
     * @param Closure|null $query
     *
     * @return BelongsToMany
     */
    protected function belongsToMany(
        string $relation,
        string $table,
        ?string $foreignKey = null,
        ?string $relationForeignKey = null,
        ?Closure $query = null
    ): BelongsToMany {
        return new BelongsToMany($relation, $this, $table, $foreignKey, $relationForeignKey, $query);
    }

    /**
     * @param string       $relation
     * @param string       $foreignKey
     * @param string|null  $primaryKey
     * @param Closure|null $query
     *
     * @return HasMany
     */
    protected function hasMany(
        string $relation,
        string $foreignKey,
        ?string $primaryKey = null,
        ?Closure $query = null
    ): HasMany {
        return new HasMany($relation, $this, $foreignKey, $primaryKey ?: $this->getPrimaryKey(), $query);
    }

    /**
     * @return BaseModel
     */
    public function initRelations(): BaseModel
    {
        if ($this->wasInitialized) {
            return $this;
        }

        $this->wasInitialized = true;

        $correctReturnTypes = [
            'Framework\Model\Relation\RelationTypes\BelongsTo',
            'Framework\Model\Relation\RelationTypes\BelongsToMany',
            'Framework\Model\Relation\RelationTypes\HasOne',
            'Framework\Model\Relation\RelationTypes\HasMany',
        ];

        collection((new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->map(fn (ReflectionMethod $method) => [
                (string) $method->getReturnType(),
                $method->getName(),
            ])
            ->filter(fn (array $method) => in_array($method[0], $correctReturnTypes))
            ->each(function (array $method) {
                $this->relations[$method[1]] = $this->{$method[1]}()->setName($method[1]);
            });

        return $this;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param string $name
     *
     * @return BaseRelation
     */
    public function getRelation(string $name): BaseRelation
    {
        if (!isset($this->getRelations()[$name])) {
            throw new Exception("There was no relation named [{$name}] found!");
        }

        return $this->getRelations()[$name];
    }

    /**
     * @param string $name
     *
     * @return BaseModel|Collection|Paginator
     */
    protected function getRelationData(string $name): BaseModel|Collection|Paginator
    {
        $relation = $this->getRelation($name);

        return $relation->getData([$relation->getFromModel()]);
    }
}
