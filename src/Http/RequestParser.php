<?php

declare(strict_types=1);

namespace Framework\Http;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations, 
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).  
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
trait RequestParser
{
    /**
     * @return string
     */
    protected function parseProtocol(): string
    {
        return $this->server->get('SERVER_PROTOCOL');
    }

    /**
     * @return string
     */
    protected function parseHost(): string
    {
        return $this->server->get('HTTP_HOST');
    }

    /**
     * @return string
     */
    protected function parseUri(): string
    {
        return parse_url(
            rawurldecode($this->url()),
            PHP_URL_PATH
        ) ?? '';
    }

    /**
     * @return string
     */
    protected function parseQuery(): string
    {
        return parse_url(
            rawurldecode($this->url()),
            PHP_URL_QUERY
        ) ?? '';
    }
}
