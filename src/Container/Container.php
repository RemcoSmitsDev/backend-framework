<?php

namespace Framework\Container;

use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Container
{
    /**
     * @var array
     */
    private static array $arguments = [];

    /**
     * @param array<string,mixed> $data
     * @return self
     */
    public static function setData(array $data): self
    {
        self::$arguments = array_merge(self::$arguments, $data);

        return new self;
    }

    /**
     * Get all parameters from class method reflection
     *
     * @param  callable               $callback
     * @param  array<string,mixed>    $arguments
     * @param  array<int,mixed>       $parameters
     * @return mixed
     */
    public static function handleClosure(callable $callback, array $arguments = [], array &$parameters = []): mixed
    {
        // make arguments global
        self::$arguments = array_merge(self::$arguments, $arguments);

        // get parameters
        $parameters = self::getParameters(new ReflectionFunction(Closure::fromCallable($callback)));

        // return parameters bij reflection of closure function
        return call_user_func(
            $callback,
            ...$parameters
        );
    }

    /**
     * Get all parameters from class method reflection.
     *
     * @param object|class-string<object> $class
     * @param string                      $method
     * @param array<string,mixed>         $arguments
     * @param object|null                 $classInstance
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public static function handleClassMethod(object|string $class, string $method, array $arguments = [], ?object &$classInstance = null): mixed
    {
        // make arguments global
        self::$arguments = array_merge(self::$arguments, $arguments);

        // validate class
        [$classInstance, $reflectionMethod] = self::validateClassMethod($class, $method);

        // call method with parameters and return response
        return call_user_func(
            [$classInstance, $method],
            ...self::getParameters($reflectionMethod)
        );
    }

    /**
     * @param object|string $class
     * @param string $method
     * @return array<object,ReflectionMethod>
     * 
     * @throws Exception
     * @throws ReflectionException
     */
    public static function validateClassMethod(object|string $class, string $method): array
    {
        // checking if the class exists
        if (!class_exists(is_object($class) ? $class::class : $class)) {
            throw new Exception("Class not found {$class}!");
        }

        // make reflection of class
        $reflectionClass = new ReflectionClass($class);

        // check if class has method
        if (!$reflectionClass->hasMethod($method)) {
            throw new Exception("The method `{$method}` does not exists on the `{$class}` class!");
        }

        // make instance of class
        $classInstance = $reflectionClass->newInstance(
            ...($reflectionClass->getConstructor() && $reflectionClass->getMethod('__construct')->isPublic() ? Container::getParameters($reflectionClass->getConstructor()) : [])
        );

        // make reflection
        $reflectionMethod = new ReflectionMethod($classInstance, $method);

        // check if method is public
        if (!$reflectionMethod->isPublic()) {
            throw new ReflectionException("The method `{$method}` on `{$class}` must be public!");
        }

        return [
            $classInstance,
            $reflectionMethod
        ];
    }

    /**
     * Get all parameters from function/method reflection.
     *
     * @param ReflectionMethod|ReflectionFunction $reflection
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return array<int,mixed>
     */
    public static function getParameters(ReflectionMethod|ReflectionFunction $reflection): array
    {
        // keep track of dependencies
        $dependencies = [];

        // loop trough parameters
        foreach ($reflection->getParameters() as $parameter) {
            // check if has type
            if (!$parameter->hasType()) {
                // handle non typed parameters
                self::handleAddDepenciesFromArguments($parameter, $dependencies);

                continue;
            }

            // define type of variabel to string
            $type = $parameter->getType();

            // check if is buildin type(array, string, int, float, null)
            if ($type instanceof ReflectionUnionType || $type instanceof ReflectionNamedType && $type->isBuiltin()) {
                // handle buldint typed parameters
                self::handleAddDepenciesFromArguments($parameter, $dependencies);

                continue;
            }

            // make reflection of class
            $reflect = new ReflectionClass($type = (string) $type);

            // check if is interface
            if ($reflect->isInterface() || !$reflect->IsInstantiable()) {
                // append to dependencies
                self::handleAddDepenciesFromArguments($parameter, $dependencies);

                // go to the next in the array
                continue;
            }

            // add dependency
            $dependencies[] = $reflect->newInstance(
                ...($reflect->getConstructor() && $reflect->getMethod('__construct')->isPublic() ? (new self())::getParameters($reflect->getConstructor()) : [])
            );
        }

        return $dependencies;
    }

    /**
     * This method will handle/validate parameters with arguments.
     *
     * @param ReflectionParameter $parameter
     * @param array               $dependencies
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private static function handleAddDepenciesFromArguments(ReflectionParameter $parameter, array &$dependencies)
    {
        // check if parameter exists inside arguments
        $foundArgument = array_key_exists($parameter->getName(), self::$arguments);

        // when has default value or can be null and params does not exist in the given arguments
        if ($parameter->isDefaultValueAvailable() && !$foundArgument) {
            // append default value
            $dependencies[] = $parameter->getDefaultValue();

            return;
        }

        if ($parameter->allowsNull() && !$foundArgument) {
            // append dependency
            $dependencies[] = null;

            return;
        }

        if ($parameter->isOptional() && !$foundArgument) {
            // append dependency
            $dependencies[] = $parameter->getDefaultValueConstantName();

            return;
        }

        // when the param does not exists inside the given arguments
        if (!$foundArgument) {
            throw new InvalidArgumentException('You must have a argument value inside the `handleClassMethod` or `handleClosure` with the name: `' . $parameter->getName() . '`');
        }

        // add dependency
        $dependencies[] = self::$arguments[$parameter->getName()];
    }
}
