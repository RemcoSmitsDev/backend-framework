<?php

namespace Framework\Container;

use Framework\Interfaces\ContainerInterface;
use ReflectionException;
use ReflectionNamedType;
use ReflectionFunction;
use ReflectionMethod;
use Exception;

class Container implements ContainerInterface
{
    /**
     * handleClosure function
     * get all parameters from class method reflection
     * @param callable $callback
     * @param array $arguments = []
     * @return array parameters
     * @throws ReflectionException
     */

    public static function handleClosure(callable $callback, array $arguments = []): array
    {
        // return parameters bij reflection of closure function
        return self::getParameters(new ReflectionFunction($callback), $arguments);
    }

    /**
     * handleClassMethod function
     * get all parameters from class method reflection
     * @param string $className
     * @param string $method
     * @param array $arguments
     * @return array parameters
     * @throws ReflectionException
     * @throws Exception
     */

    public static function handleClassMethod(string $className, string $method, array $arguments = []): array
    {
        // checking if the class exists
        if (!class_exists($className)) {
            throw new Exception("Class not found {$className}");
        }

        // initialized the ReflectionMethod and return parameters
        return self::getParameters(new ReflectionMethod($className, $method), $arguments);
    }

    /**
     * getParameters function
     * get all parameters from function/method reflection
     * @param ReflectionMethod|ReflectionFunction $reflection
     * @param array $parameters
     * @return array $dependencies
     * @throws ReflectionException
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
                    $dependencies[] = $parameters[$parameter->getName()];
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
            if (isset(app()->{$objectName = lcfirst($reflect->getShortName())})) {
                // set class
                $dependencies[] = app()->{$objectName};
            } else {
                // make instance of class
                $dependencies[] = new $type();
            }
        }

        return $dependencies;
    }
}
