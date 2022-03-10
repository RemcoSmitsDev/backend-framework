<?php

namespace Framework\Model\Relation\RelationTypes;

use Closure;
use Exception;
use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\Paginator\Paginator;
use Framework\Model\BaseModel;
use Framework\Model\Relation\BaseRelation;

class BelongsTo extends BaseRelation
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
	 * @param array  $results
	 * 
	 * @throws Exception
	 * 
	 * @return BaseModel
	 */
	public function getData(array $results = []): BaseModel
	{
		$fromModel = $this->getFromModel();

		if (!property_exists($fromModel->getOriginal(), $this->foreignKey)) {
			throw new Exception("There doesn't exists a property called [{$this->foreignKey}]!");
		}

		$query = $this->buildWhereInQuery($this->primaryKey, [$fromModel->getOriginal()->{$this->foreignKey}]);

		$query = $this->query ? ($this->query)($query) : $query;

		return $query->one();
	}

	public function mergeRelation($baseData, $relationData): BaseModel|Collection|Paginator
	{
		// when fetched with `one()`
		if ($baseData instanceof BaseModel) {

			$baseData->{$this->getName()} = $this->getData();

			return $baseData;
		}

		return $baseData;
	}
}
