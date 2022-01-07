<?php

namespace Framework\Database\Relations;

use ReflectionClass;
use ReflectionMethod;

trait HasRelations
{
	public function getAllRelations(object $baseModel)
	{
		// make new class reflection
		$reflection = new ReflectionClass($baseModel);

		// get all methods
		$methods = $reflection->getMethods();

		// make collection and filter methods that will give info about relation
		$methods = collection($methods)->filter(function (ReflectionMethod $item) {
			return $item->isProtected() && $item->getName() !== 'belongsTo' && $item->getName() !== 'hasMany';
		})->filter(function ($item) {
			return str_starts_with($item->getName(), 'belongsTo') || str_starts_with($item->getName(), 'hasMany');
		});

		// check if there was an item found
		if (!$methods->first()) {
			return;
		}

		// loop over
		$this->relations = $methods->map(function ($item, $key) {
			return $this->{$item->getName()}();
		})->filter(function ($relation) {
			return $relation instanceof HasMany || $relation instanceof BelongsTo;
		})->toArray();

		// dd($this->relations);

		// get belongs to
		// $belongsTo = str_replace(['belongsTo', 'hasMany'], '', $methods->first()->getName());

		// $belongsTo = $this->formatTableName($belongsTo);

		// BELONGS TO:
		// $this->logSql()->join($belongsTo, $belongsTo . '.' . $this->primaryKey, '=', $this->formatTableName($methods->first()->class) . '.' . substr($belongsTo, 0, -1) . '_id');

		// HAS MANY:
		// $this->logSql()->join(
		//     $this->formatTableName($methods->first()->class),
		//     $this->formatTableName($belongsTo) . '.' . substr($this->formatTableName($methods->first()->class), 0, -1) . '_id',
		//     '=',
		//     $this->formatTableName($methods->first()->class) . '.' . 'id'
		// );
	}
}
