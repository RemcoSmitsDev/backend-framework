<?php

namespace Framework\DependencyInjectionContainer;

use Framework\Interfaces\DependencyInjectionContainerInterface;

class DependencyInjectionContainer implements DependencyInjectionContainerInterface
{
    /**
     * handleClosure function
     * get alle parameters from class method reflection
     * @param |Closure $callback
     * @param array $arguments = []
     * @return array parameters
     */

    public static function handleClosure(\Closure $callback, array $parameters = []): array
    {
        // return parameters bij reflection of closure function
        return self::getParameters(new \ReflectionFunction($callback), $parameters);
    }

    /**
     * handleClassMethod function
     * get alle parameters from class method reflection
     * @param string $className
     * @param string $method
     * @param array $arguments = []
     * @return array parameters
     */

    public static function handleClassMethod(string $className, string $method, array $parameters = [])
    {
        // checking if the class exists
        if (!class_exists($className)) {
            throw new \Exception("Class not found {$className}");
        }

        // initialized the ReflectionMethod and return parameters
        return self::getParameters(new \ReflectionMethod($className, $method), $parameters);
    }

    /**
     * getParameters function
     * get alle parameters from function/method reflection
     * @param \ReflectionMethod|\ReflectionFunction $reflection
     * @return array $dependencies
     */

    private static function getParameters(\ReflectionMethod|\ReflectionFunction $reflection, array $parameters = [])
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
