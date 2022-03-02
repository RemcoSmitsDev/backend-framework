<?php

namespace Framework\Container;

use Closure;

class Container
{
    /**
     * @var array<class-string, object>
     */
    private array $bindings = [];

    /**
     * @var object[]
     */
    private array $singletons = [];

    /**
     * @var Container|null
     */
    private static ?Container $instance = null;

    /**
     * Returns the singleton instance
     * 
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Bind contract to class and store inside the container bindings
     * 
     * @param class-string $contract
     * @param object $callback
     * @return self
     */
    public function bind(string $contract, object $callback): self
    {
        $this->bindings[$contract] = $callback;

        return $this;
    }

    /**
     * Get class by contract from the container bindings
     * 
     * @param class-string $contract
     * @return object|null
     */
    public function getBinding(string $contract): ?object
    {
        $binding = $this->bindings[$contract] ?? null;

        if (!$binding instanceof Closure) return $binding;

        return ($binding)();
    }

    /**
     * Add a singleton to the container singletons
     * 
     * @template T of object
     * @param T $class
     * @return T
     */
    public function addSingleton(object $class)
    {
        $this->singletons[$class::class] = $class;

        return $class;
    }

    /**
     * Get a singleton from the container singletons
     * 
     * @param class-string $class
     * @return object|null
     */
    public function getSingleton(string $class): ?object
    {
        return $this->singletons[$class] ?? null;
    }
}
