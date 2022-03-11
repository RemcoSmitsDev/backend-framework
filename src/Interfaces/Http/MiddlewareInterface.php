<?php

declare(strict_types=1);

namespace Framework\Interfaces\Http;

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
interface MiddlewareInterface
{
    /**
     * @param array $route
     * @param array $middlewareRules
     *
     * @return bool
     */
    public static function validate(array $route, array $middlewareRules): bool;
}
