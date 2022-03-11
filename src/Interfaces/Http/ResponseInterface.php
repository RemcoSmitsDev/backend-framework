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
interface ResponseInterface
{
    /**
     * @param array $responseData
     *
     * @return $this
     */
    public function json(array $responseData): self;

    /**
     * @param string $responseData
     *
     * @return $this
     */
    public function text(string $responseData): self;

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function headers(array $headers): self;

    /**
     * @param int $responseCode
     *
     * @return $this
     */
    public function code(int $responseCode = 200): self;
}
