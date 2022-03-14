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
final class RequestCookie extends GetAble
{
    /**
     * @var array
     */
    protected array $cookies = [];

    /**
     * @param RequestHeader $headers
     */
    public function __construct(
        RequestHeader $headers
    ) {
        // init getable
        parent::__construct('cookies');

        // set headers
        $cookies = $headers->get('cookie', '');

        // explode cookies
        $cookies = explode('; ', $cookies);

        // check if empty cookies
        if (empty($cookies[0])) {
            return;
        }

        // get cookies into the right format
        array_walk($cookies, function (&$cookie) {
            $parts = explode('=', $cookie, 2);

            $cookie = [
                ($parts[0] ?? '') => $parts[1] ?? null,
            ];
        });

        // flatten to one layer
        $this->cookies = collection(array_merge(...$cookies))->filter()->all();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        // get all cookies
        $cookies = $this->all();

        // format the cookies into a valid string
        array_walk($cookies, function (&$value, $key) {
            $value = "{$key}={$value}";
        });

        // make string separate `; `
        return implode('; ', $cookies);
    }
}
