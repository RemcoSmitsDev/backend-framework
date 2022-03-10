<?php

namespace Framework\Model\Relation\RelationTypes;

use Exception;
use Framework\Model\BaseModel;
use Framework\Model\Relation\BaseRelation;

class HasMany extends BaseRelation
{
	/**
	 * @param class-string $relation
	 * @param string 	   $foreignKey
	 * @param string|null  $primaryKey
	 */
	public function __construct(
		public string  $relation,
		public string $foreignKey,
		public ?string $primaryKey = null,
	) {
	}

	/**
	 * @param  array  $results
	 * 
	 * @return BaseModel
	 */
	public function getData(array $results = []): BaseModel
	{
		$fromModel = $this->getFromModel();

		$query = $this->buildWhereInQuery(
			$this->getHasOneRelation($fromModel)->foreignKey,
			[$fromModel->getOriginal()->{$this->primaryKey}]
		);

		$query = $this->query ? ($this->query)($query) : $query;

		return $query->one();
	}

	/**
	 * @param  BaseModel
	 * 
	 * @return BaseRelation
	 */
	private function getHasOneRelation(BaseModel $fromModel): BaseRelation
	{
		$hasOne = collection($this->getRelationInstance()->initRelations()->getRelations())
			->filter(fn ($relation) => $relation->relation == $fromModel::class)
			->first();

		if (!$hasOne) throw new Exception('There was no relation found for [' . $fromModel::class . ']!');

		return $hasOne;
	}
}
