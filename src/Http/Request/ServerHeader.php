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
final class ServerHeader extends GetAble
{
    /**
     * @var array
     */
    protected array $headers = [];

    public function __construct()
    {
        // init getable
        parent::__construct('headers');

        // merge all headers
        $this->headers = $_SERVER;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        // get all headers
        $headers = $this->all();

        // format the header into a valid string
        array_walk($headers, function (&$value, $key) {
            $value = "{$key}: {$value}";
        });

        // make string with ` ` separator
        return implode(' ', $headers);
    }
}
