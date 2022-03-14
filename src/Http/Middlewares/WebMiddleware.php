<?php

declare(strict_types=1);

namespace Framework\Http\Middlewares;

use Closure;
use Framework\Http\Api;

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
class WebMiddleware
{
    /**
     * @param array   $route
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(array $route, Closure $next): mixed
    {
        // when is not from own server or is from ajax request
        if (Api::fromAjax() || !Api::fromOwnServer()) {
            return false;
        }

        // when is not get request make sure that CSRF token is validated
        if (request()->method() !== 'GET' && !request()->validateCsrf()) {
            abort(403);
        }

        // return next action
        return $next();
    }
}
