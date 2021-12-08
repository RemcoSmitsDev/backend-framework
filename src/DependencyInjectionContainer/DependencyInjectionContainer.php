<?php

namespace Framework\DependencyInjectionContainer;

use Exception;
use Framework\Interfaces\DependencyInjectionContainerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class DependencyInjectionContainer implements DependencyInjectionContainerInterface
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
     * @param array $arguments = []
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
        // types to skip
        $skipTypes = ['string', 'array', 'object', 'stdclass'];

        // dependencies
        $dependencies = [];

        // loop trough parameters
        foreach ($reflection->getParameters() as $parameter) {

            // check if type exists
            if (!($type = $parameter->getType())) {
                // check if parameter already exists
                if (isset($parameters[$name = $parameter->getName()])) {
                    $dependencies[] = $parameters[$name];
                }
                // go to the next in the array
                continue;
            }

            // define type of variabel to string
            $type = (string)$type;

            // check if type is in skip array
            if (in_array(strtolower($type), $skipTypes)) {
                // add type as depency
                $dependencies[] = $type;
                // go to the next in the function
                continue;
            }

            // make reflection of class
            $reflect = new \ReflectionClass($type);

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
