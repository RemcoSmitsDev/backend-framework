<?php

declare(strict_types=1);

namespace Framework\Event\DefaultEvents;

use Framework\Database\SqlFormatter;
use Framework\Debug\Debug;
use Framework\Event\BaseEvent;
use Framework\Event\Interfaces\BaseEventInterface;

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
class QueryEvent extends BaseEvent
{
    /**
     * @param BaseEventInterface $event
     * @param array|null         $data
     *
     * @return void
     */
    public function handle(BaseEventInterface $event, $data): void
    {
        // check if can show error message
        if (!defined('IS_DEVELOPMENT_MODE') || !IS_DEVELOPMENT_MODE) {
            return;
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
            'Execution time: '.$data['executionTime'].' seconds',
        ])->filter(fn ($value) => $value != '--skip--');

        // send to ray
        ray(
            ...$collection
        )->type('query')->color($data['error'] ? 'red' : '');
    }
}
