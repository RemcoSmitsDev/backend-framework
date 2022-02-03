<?php

namespace Framework\Container;

use Framework\Interfaces\ContainerInterface;
use InvalidArgumentException;
use ReflectionException;
use ReflectionNamedType;
use ReflectionFunction;
use ReflectionMethod;
use Exception;
use Closure;

class Container implements ContainerInterface
{
    /**
     * Get all parameters from class method reflection
     *
     * @param  callable $callback
     * @param  array<string,mixed>    $arguments
     * @return array<int,mixed>
     */
    public static function handleClosure(callable $callback, array $arguments = []): array
    {
        // return parameters bij reflection of closure function
        return self::getParameters(new ReflectionFunction(Closure::fromCallable($callback)), $arguments);
    }

    /**
     * Get all parameters from class method reflection
     *
     * @param  string $className
     * @param  string $method
     * @param  array<string,mixed>  $arguments
     * @return array<int,mixed>
     */
    public static function handleClassMethod(string $className, string $method, array $arguments = []): array
    {
        // checking if the class exists
        if (!class_exists($className)) {
            throw new Exception("Class not found {$className}");
        }

        // make reflection
        $reflectMethod = new ReflectionMethod($className, $method);

        // check if method is public
        if (!$reflectMethod->isPublic()) {
            throw new ReflectionException("The method `{$method}` on `{$className}` must be public!");
        }

        // initialized the ReflectionMethod and return parameters
        return self::getParameters($reflectMethod, $arguments);
    }

    /**
     * Get all parameters from function/method reflection
     *
     * @param  ReflectionMethod|ReflectionFunction $reflection
     * @param  array<string,mixed>                 $parameters
     * @return array<int,mixed>
     */
    private static function getParameters(ReflectionMethod|ReflectionFunction $reflection, array $parameters = []): array
    {
        // dependencies
        $dependencies = [];

        // loop trough parameters
        foreach ($reflection->getParameters() as $parameter) {

            // define type of variabel to string
            $type = $parameter->getType();

            // check if type exists
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || !$parameter->hasType() || interface_exists((string) $type)) {
                // check if parameter already exists
                if (array_key_exists($parameter->getName(), $parameters)) {
                    // add dependency
                    $dependencies[] = $parameters[$parameter->getName()];

                    // unset param to keep track of used params
                    unset($parameters[$parameter->getName()]);

                    continue;
                }
                // go to the next in the array
                continue;
            }

            // get param type
            $type = (string) $type;

            // make reflection of class
            $reflect = new \ReflectionClass($type);

            // check if is interface
            if ($reflect->isInterface()) {
                // append to dependencies
                $dependencies[] = $parameters[$parameter->getName()];

                // go to the next in the array
                continue;
            }

            // check if there already exists an instance of the class in the app class
            if (app($objectName = lcfirst($reflect->getShortName()))) {
                // set class
                $dependencies[] = app($objectName);
            } else {
                // make instance of class
                $dependencies[] = $reflect->newInstance(...($reflect->getConstructor() ? self::handleClassMethod($type, '__construct') : []));
            }
        }

        // check if there are required parameters left
        if (!empty($parameters)) {
            // get required param variable names
            $names = implode('`,`', array_keys($parameters));

            // throw exception not used all parameters
            throw new InvalidArgumentException("You must have all required parameters! variable names: `{$names}`");
        }

        return $dependencies;
    }
}
