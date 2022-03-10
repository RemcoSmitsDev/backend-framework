<?php

namespace Framework\Model\Relation\RelationTypes;

use Closure;
use Exception;
use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\Paginator\Paginator;
use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Model\BaseModel;
use Framework\Model\Relation\BaseRelation;

class HasMany extends BaseRelation
{
	/**
	 * @param string        $relation
	 * @param BaseModel     $fromModel
	 * @param string        $foreignKey
	 * @param string|null   $primaryKey
	 * @param Closure|null  $query
	 */
	public function __construct(
		public string  $relation,
		protected BaseModel $fromModel,
		public string $foreignKey,
		public ?string $primaryKey = null,
		public ?Closure $query = null
	) {
	}

	/**
	 * @param  array  $results
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

		if($query instanceof QueryBuilder) return $query->all();

		return $query;
	}

	/**
	 * @param  BaseModel  $fromModel
	 * 
	 * @return BaseRelation
	 */
	private function getHasManyRelation(BaseModel $fromModel): BaseRelation
	{
		$hasMany = collection($this->getRelationInstance()->initRelations()->getRelations())
			->filter(fn ($relation) => $relation->relation === $fromModel::class)
			->first();

		if (!$hasMany) throw new Exception('There was no relation found for [' . $fromModel::class . ']!');

		return $hasMany;
	}

	public function mergeRelation($baseData, $relationData): BaseModel|Collection|Paginator
	{
		return $baseData;
	}
}
