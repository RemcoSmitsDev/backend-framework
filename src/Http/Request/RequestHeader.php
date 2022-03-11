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
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
final class RequestHeader extends GetAble
{
    /**
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * @param ServerHeader $server
     */
    public function __construct(
        private ServerHeader $server
    ) {
        // init getable
        parent::__construct('headers');

        // merge all headers
        $this->headers = function_exists('getallheaders') ? getallheaders() : $server->all();
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function __get(string $name): ?string
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->headers[$name] = $value;
    }
}
