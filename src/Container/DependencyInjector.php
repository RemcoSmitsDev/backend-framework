<?php

namespace Framework\Container;

use Closure;
use Exception;
use Framework\Container\Exceptions\InvalidClassException;
use Framework\Container\Exceptions\InvalidClassMethodException;
use Framework\Container\Exceptions\MissingArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class DependencyInjector
{
	/**
	 * @var ReflectionClass|null
	 */
	private ?ReflectionClass $reflectionClass = null;

	/**
	 * @var object
	 */
	private ?object $classInstance = null;

	/**
	 * @var array
	 */
	private array $arguments = [];

	/**
	 * @param class-string|Closure|object $target
	 * @param string|null $method
	 * @return void
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws InvalidClassException
	 */
	public function __construct(
		private string|object $target,
		private ?string $method
	) {
		if (!$this->target instanceof Closure) {
			$this->validateClassExists();

			$this->reflectionClass = new ReflectionClass($this->target);
		}
	}

	/**
	 * Make a new instance of DependencyInjector
	 * 
	 * @param class-string|Closure|object $target
	 * @param string|null $method
	 * @return DependencyInjector
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 */
	public static function resolve(string|object $target, ?string $method = null): self
	{
		return new self($target, $method);
	}

	/**
	 * Sets the arguments that need to be used when a parameter doesn't have a default or instaniable value
	 * 
	 * @param array $arguments
	 * @return self
	 */
	public function with(array $arguments): self
	{
		$this->arguments = $arguments;

		return $this;
	}

	/**
	 * Will resolve all parameters and returns the value of the callable/method
	 * 
	 * @return mixed
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws InvalidClassException
	 * @throws MissingArgumentException
	 * @throws InvalidMethodNameException
	 */
	public function getContent(): mixed
	{
		return $this->target instanceof Closure ? $this->resolveClosure() : $this->resolveClassMethod();
	}

	/**
	 * Gets all parameter values
	 * 
	 * @return array
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws InvalidClassException
	 * @throws MissingArgumentException
	 * @throws InvalidMethodNameException
	 */
	public function getParameters(): array
	{
		return $this->target instanceof Closure ? $this->getClosureParameters() : $this->getClassMethodParameters();
	}

	/**
	 * Returns the class instance when resolving a class method
	 * 
	 * @return object|null
	 */
	public function getClassInstance(): ?object
	{
		return $this->classInstance;
	}

	/**
	 * Gets the current reflectionClass instance
	 * 
	 * @return ReflectionClass|null
	 */
	public function getReflectionClass(): ?ReflectionClass
	{
		return $this->reflectionClass;
	}

	/**
	 * Resolves closure and returns closure return value
	 * 
	 * @return mixed
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws MissingArgumentException
	 */
	private function resolveClosure(): mixed
	{
		return call_user_func_array($this->target, $this->getClosureParameters());
	}

	/**
	 * Gets all closure parameter values
	 * 
	 * @return array
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws MissingArgumentException
	 */
	private function getClosureParameters(): array
	{
		return $this->resolveParameters(
			new ReflectionFunction(Closure::fromCallable($this->target))
		);
	}

	/**
	 * Resolves class method and returns method return value
	 * 
	 * @return mixed
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws InvalidClassException
	 * @throws MissingArgumentException
	 * @throws InvalidMethodNameException
	 */
	private function resolveClassMethod(): mixed
	{
		if (!$this->getReflectionClass()->getConstructor()) {
			return $this->method === null ?
				$this->getReflectionClass()->newInstance() :
				call_user_func_array(
					[$this->getReflectionClass()->newInstance(), $this->method],
					$this->getParameters()
				);
		}

		$this->classInstance = is_object($this->target) ?
			$this->target :
			$this->getReflectionClass()->newInstanceArgs(
				$this->resolveParameters($this->getReflectionClass()->getConstructor())
			);

		return $this->method === null ?
			$this->classInstance :
			call_user_func_array([$this->classInstance, $this->method], $this->getParameters());
	}

	/**
	 * Gets all parameter values from a class method
	 * 
	 * @return array
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws InvalidClassException
	 * @throws MissingArgumentException
	 * @throws InvalidMethodNameException
	 */
	private function getClassMethodParameters(): array
	{
		$this->validateClassExists();

		$this->validateMethod();

		return $this->resolveParameters(
			$this->getReflectionClass()->getMethod($this->method)
		);
	}

	/**
	 * Gets all parameters by a function/method
	 * 
	 * @param ReflectionFunction|ReflectionMethod $reflectionFunction
	 * @return array<string, mixed>
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws InvalidClassException
	 * @throws MissingArgumentException
	 * @throws InvalidMethodNameException
	 */
	private function resolveParameters(ReflectionFunction|ReflectionMethod $reflectionFunction): array
	{
		$reflectionParameters = $reflectionFunction->getParameters();

		$parameters = [];

		collection($reflectionParameters)->each(function ($parameter) use (&$parameters) {
			$parameters[$parameter->getName()] = $this->resolveParameter($parameter);
		});

		return $parameters;
	}

	/**
	 * Resolves a single parameter and returns the value
	 * 
	 * @param ReflectionParameter $parameter
	 * @return mixed
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws MissingArgumentException
	 */
	private function resolveParameter(ReflectionParameter $parameter): mixed
	{
		if ($parameter->getType() instanceof ReflectionNamedType && $parameter->getType()->isBuiltin() || $parameter->getType() instanceof ReflectionUnionType) {
			return $this->getParameterValueFromArguments($parameter);
		}

		if ($parameter->getType() instanceof ReflectionNamedType) return $this->getParameterFormNamedType($parameter);

		return $this->getParameterValueFromArguments($parameter);
	}

	/**
	 * Gets a parameter value based on the type, default value, given arguments
	 * 
	 * @param ReflectionParameter $parameter
	 * @return mixed
	 * 
	 * @throws ReflectionException
	 * @throws MissingArgumentException
	 */
	private function getParameterValueFromArguments(ReflectionParameter $parameter): mixed
	{
		$foundArgument = array_key_exists($parameter->getName(), $this->arguments);

		if (!$foundArgument && $parameter->isDefaultValueAvailable()) return $parameter->getDefaultValue();

		if ($parameter->allowsNull() && !$foundArgument) return null;

		if ($parameter->isOptional() && !$foundArgument && !$parameter->isVariadic()) return $parameter->getDefaultValueConstantName();

		if (!$foundArgument) {
			throw new MissingArgumentException('You must add the `' . $parameter->getName() . '` argument!');
		}

		return $this->arguments[$parameter->getName()];
	}

	/**
	 * Gets the parameter value by named type like `Post $post`
	 * 
	 * @param ReflectionParameter $parameter
	 * @return class-object
	 * 
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws MissingArgumentException
	 */
	private function getParameterFormNamedType(ReflectionParameter $parameter): object
	{
		$foundArgument = array_key_exists($parameter->getName(), $this->arguments);

		$injector = DependencyInjector::resolve(target: (string) $parameter->getType())->with(arguments: $this->arguments);

		if ($foundArgument && is_object($this->arguments[$parameter->getName()]) && $injector->getReflectionClass() && $injector->getReflectionClass()->isInstance($this->arguments[$parameter->getName()])) {
			return $this->arguments[$parameter->getName()];
		}

		if ($injector->getReflectionClass() && (!$injector->getReflectionClass()->isInstantiable() || $injector->getReflectionClass()->isInterface())) {
			$binding = Container::getInstance()->getBinding((string) $parameter->getType());

			if ($binding) return $binding;

			throw new Exception('There was no binding found!');
		}

		$singleton = Container::getInstance()->getSingleton((string) $parameter->getType());

		if ($singleton) return $singleton;

		if ($injector->getReflectionClass()->getConstructor()) {
			return $injector->getReflectionClass()->newInstanceArgs(
				$injector->resolveParameters(
					$injector->getReflectionClass()->getConstructor()
				)
			);
		}

		return $injector->getReflectionClass()->newInstance();
	}

	/**
	 * Validates that a class is valid and can be used
	 * 
	 * @return void
	 * 
	 * @throws InvalidClassException
	 */
	private function validateClassExists(): void
	{
		if (!$this->target || (is_string($this->target) && !class_exists($this->target) && !interface_exists((string) $this->target))) {
			throw new InvalidClassException("The given target is not a class!");
		}
	}

	/**
	 * Validates that a method exists and can be used
	 * 
	 * @return void
	 * 
	 * @throws InvalidMethodNameException
	 */
	private function validateMethod(): void
	{
		if ($this->method === null) return;

		if (!$this->getReflectionClass() || !$this->getReflectionClass()->hasMethod($this->method)) {
			throw new InvalidClassMethodException("The method {$this->method} does not exists!");
		}

		if (!$this->getReflectionClass()->getMethod($this->method)->isPublic()) {
			throw new InvalidClassMethodException("The method `{$this->method}` must be public inside the `" . $this->getReflectionClass()->getName() . "`");
		}
	}
}
