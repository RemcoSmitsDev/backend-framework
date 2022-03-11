<?php

declare(strict_types=1);

namespace Framework\Event\DefaultEvents;

use Error;
use ErrorException;
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
class ErrorEvent extends BaseEvent
{
    /**
     * @param BaseEventInterface $event
     * @param mixed              $data
     *
     * @return void
     */
    public function handle(BaseEventInterface $event, mixed $data): void
    {
        if (!IS_DEVELOPMENT_MODE) {
            return;
        }

        // check if is an erroy
        if ($data['data'] instanceof ErrorException || $data['data'] instanceof Error) {
            $data['type'] = 'Error';
        }

        // add to debug state
        Debug::add('errors', $data);

        // when ray is enabled send error
        if (app()->rayIsEnabled()) {
            ray($data['data'])->title('Error')->color('red');
        }
    }
}
