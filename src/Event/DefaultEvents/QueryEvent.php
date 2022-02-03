<?php

namespace Framework\Event\DefaultEvents;

use Framework\Event\Interfaces\BaseEventInterface;
use Framework\Database\SqlFormatter;
use Framework\Debug\Debug;
use Framework\Event\BaseEvent;

class QueryEvent extends BaseEvent
{
	/**
	 * This method will handle all incoming databaseQuery events
	 *
	 * @param BaseEventInterface $event
	 * @param array|null $data
	 * @return void
	 */
	public function handle(BaseEventInterface $event, $data)
	{
		// check if can show error message
		if (!defined('IS_DEVELOPMENT_MODE') || !IS_DEVELOPMENT_MODE) {
			return false;
		}

		// append query to debug state
		Debug::add('queries', $data);

		// check if need to show query
		if (!$data['show'] ?? false || !app()->rayIsEnabled()) {
			return;
		}

		// formate data
		$collection = collection([
			SqlFormatter::format($data['query']),
			$data['error'] ?: '--skip--',
			$data['bindings'],
			'Execution time: ' . $data['executionTime'] . ' seconds'
		])->filter(fn ($value) => $value != '--skip--');

		// send to ray
		ray(
			...$collection
		)->type('query')->color($data['error'] ? 'red' : '');
	}
}