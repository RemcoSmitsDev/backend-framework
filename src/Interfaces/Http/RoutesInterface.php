<?php

namespace Framework\Interfaces\Http;

use Closure;

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
