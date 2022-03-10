<?php

namespace Framework\Model\Relation\RelationTypes;

use Closure;
use Exception;
use Framework\Collection\Collection;
use Framework\Database\QueryBuilder\Paginator\Paginator;
use Framework\Database\QueryBuilder\QueryBuilder;
use Framework\Model\BaseModel;
use Framework\Model\Relation\BaseRelation;

class BelongsToMany extends BaseRelation
{
	/**
	 * @var BaseModel
	 */
	private BaseModel $baseModelInstance;

	/**
	 * @param class-string $relation
	 * @param BaseRelation $fromModel
	 * @param string       $table
	 * @param string|null  $foreignKey
	 * @param string|null  $relationForeignKey
	 * @param Closure|null $query
	 */
	public function __construct(
		public string  $relation,
		protected BaseModel $fromModel,
		public string  $table,
		public ?string $foreignKey = null,
		public ?string $relationForeignKey = null,
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
		// when there where no ids found
		if (empty($results)) {
			return collection([]);
		}

		$fromModel = $this->getFromModel();

		// get belongs to many relation class
		$belongsToMany = $this->getBelongsToManyRelation($fromModel);
		$this->baseModelInstance = $baseModelInstance = new $belongsToMany->relation;

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
				array_column($results, $fromModel->getPrimaryKey())
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
		$belongsToMany = collection($fromModel->initRelations()->getRelations())->filter(fn ($relation) => $this->relation === $relation->relation)->first();

		if (!$belongsToMany) throw new Exception('There was no relation found for [' . $fromModel::class . ']!');

		return $belongsToMany;
	}

	public function mergeRelation(
		BaseModel|Collection|Paginator $baseData,
		BaseModel|Collection|Paginator $relationData
	): BaseModel|Collection|Paginator {

		if ($baseData instanceof Collection) {
			return $baseData->each(function (&$item) use ($relationData) {
				$item->{$this->getName()} = $relationData->filter(fn ($value) => $value->{'pivot_post_id'} == $item->{$item->getPrimaryKey()});
			});
		}

		if ($baseData instanceof BaseModel) {
			$baseData->{$this->getName()} = $relationData;
		}

		if ($baseData instanceof Paginator) {
			dd('fail');
			throw new Exception('paginator not implemented yet');
		}

		return $baseData;
	}
}
