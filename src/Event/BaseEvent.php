<?php

declare(strict_types=1);

namespace Framework\Event;

use Framework\Event\Interfaces\BaseEventInterface;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations, 
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).  
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
abstract class BaseEvent implements BaseEventInterface
{
    abstract public function handle(BaseEventInterface $event, ?array $data);
}
