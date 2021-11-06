<?php

namespace Framework\Interfaces;

interface DependencyInjectionContainerInterface
{
    /**
     * @param $callable
     * @param  array  $parameters
     * @return array
     */
    public static function handleClosure(\Closure $callable, array $parameters = []);

    /**
    * @param string $className
    * @param string $method
    * @param array $arguments
    * @param array  $parameters
    * @return array parameters
    */
    public static function handleClassMethod(string $className, string $method, array $arguments = []);
}
