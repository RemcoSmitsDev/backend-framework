<?php

declare(strict_types=1);

namespace Framework\Interfaces\Http;

use Closure;

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
interface RoutesInterface
{
    /**
     * @param bool|array $validateRules
     *
     * @return $this
     */
    public function middleware(bool|array $validateRules): self;

    /**
     * @param string $prefix
     *
     * @return $this
     */
    public function prefix(string $prefix): self;

    /**
     * @param Closure $action
     *
     * @return void
     */
    public function group(Closure $action): void;

    /**
     * @param array $patterns
     *
     * @return $this
     */
    public function pattern(array $patterns): self;

    /**
     * @param string $routeName
     *
     * @return $this
     */
    public function name(string $routeName): self;

    /**
     * @param string $routeName
     * @param array  $params
     *
     * @return string
     */
    public function getRouteByName(string $routeName, array $params = []): string;

    /**
     * @return array
     */
    public function getCurrentRoute(): array;
}
