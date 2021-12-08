<?php

namespace Framework\Interfaces;

interface DependencyInjectionContainerInterface
{
    /**
     * @param callable $callback
     * @param array $arguments
     * @return array
     */
    public static function handleClosure(callable $callback, array $arguments = []): array;

    /**
     * @param string $className
     * @param string $method
     * @param array $arguments
     * @return array parameters
     */
    public static function handleClassMethod(string $className, string $method, array $arguments = []): array;
}
