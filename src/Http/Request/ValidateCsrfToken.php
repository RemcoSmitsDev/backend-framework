<?php

declare(strict_types=1);

namespace Framework\Http\Request;

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
trait ValidateCsrfToken
{
    /**
     * @return bool
     */
    public function validateCsrf(): bool
    {
        // validate token
        $passed = hash_equals(\request()->post('_token') ?: '', $_SESSION['_csrf_token'] ?? randomString(40));

        // unset token
        unset($_SESSION['_csrf_token']);

        // return token validation passed status
        return $passed;
    }
}
