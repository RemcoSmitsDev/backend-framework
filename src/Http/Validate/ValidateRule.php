<?php

declare(strict_types=1);

namespace Framework\Http\Validate;

use Exception;
use Framework\DependencyInjectionContainer\DependencyInjectionContainer;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use ReflectionClass;

class ValidateRule
{
	/**
	 * Keeps track of all rules with function name to call
	 *
	 * @var array
	 */
	protected array $rules = [
		'string' => [
			'action' => 'validateStringType',
			'message' => '`{key}` moet een woord zijn.',
		],
		'int' => [
			'action' => 'validateIntType',
			'message' => '`{key}` moet een getal zijn.',
		],
		'float' => [
			'action' => 'validateFloatType',
			'message' => '`{key}` moet een kommagetal zijn.',
		],
		'array' => [
			'action' => 'validateArrayType',
			'message' => '`{key}` moet een reeks of waardes zijn.',
		],
		'min' => [
			'action' => 'validateMinLength',
			'message' => '`{key}` moet een minimale lengte hebben van `{value}`.',
		],
		'max' => [
			'action' => 'validateMaxLength',
			'message' => '`{key}` mag een maximale lengte hebben van `{value}`.',
		],
		'regex' => [
			'action' => 'validateRegex',
			'message' => '`{key}` voldoet niet aan het pattern.',
		],
		'email' => [
			'action' => 'validateEmail',
			'message' => '`{key}` moet een geldig e-mailadres zijn.'
		],
		'url' => [
			'action' => 'validateUrl',
			'message' => '`{key}` moet een geldige url zijn.'
		],
		'ip' => [
			'action' => 'validateIp',
			'message' => '`{key}` moet een geldig ip adres zijn.'
		],
		'custom' => [
			'action' => 'validateCustomRule',
			'message' => '`{key}` zou overeen moeten komen met `{value}`'
		]
	];

	/**
	 * This will keep track of the message
	 * @var string $message
	 */
	private string $message = '';

	/**
	 * This will setup the needed data
	 *
	 * @param string $rule The validation type(min|max|string|float|email)
	 * @param string $expected The expected value
	 * @param boolean $required If the rules need to be required
	 * @param mixed $value Is the value that the rule need to apply to
	 */
	public function __construct(
		protected string $rule,
		protected string $key,
		protected string $expected,
		protected bool $required,
		protected mixed $value,
	) {
	}

	/**
	 * This method will validate the rule
	 */
	public function validate()
	{
		// keep track of function to call
		$functionToCall = $this->rules['custom'];
		// args to apply
		$args = [];

		// when is custom rule (class -> validate())
		if (is_string($this->rule) && class_exists($this->rule)) {
			// return boolean based on the rule passed status
			return $this->handleCustomRule();
		}

		// check for dynamic 
		if (preg_match('/^([A-z\-_]+):(.+)$/', $this->expected, $match)) {
			// set expected value
			$this->expected = $match[2];
			// set function to call
			$functionToCall = $this->rules[$match[1]];

			// 
			$args = [
				$match[2]
			];
		} elseif (isset($this->rules[$this->expected])) {
			// set function to call
			$functionToCall = $this->rules[$this->expected];
		}

		// set message
		$this->message = str_replace(['{key}', '{value}'], [$this->key, $this->expected], $functionToCall['message']);

		// call function
		return call_user_func([$this, $functionToCall['action']], ...$args);
	}

	private function handleCustomRule()
	{
		// make reflection of class
		$reflection = new ReflectionClass($this->rule);

		// check if class has validate method
		if (!$reflection->hasMethod('validate')) {
			throw new InvalidMethodNameException("The method `validate` was not found inside the `{$this->rule}` class");
		}

		//  check if class extends CustomRule
		if (!$reflection->isSubclassOf(CustomRule::class)) {
			throw new Exception("The class `{$this->rule}` must extends `CustomRule` class.");
		}

		// call method with dependency injection container
		$parameters = DependencyInjectionContainer::handleClassMethod($this->rule, 'validate', ['value' => $this->value]);

		// make instance of class
		$customRuleClass = new ($this->rule)();

		// call validate method and get passed boolean back
		$passed = $customRuleClass->validate(...$parameters);

		// set message when there was an message set
		if (!$passed && !empty($customRuleClass->getMessage())) {
			$this->message = $customRuleClass->getMessage();
		}

		// return boolean based on the rule passed status
		return $passed;
	}

	/**
	 * This will return a message if there was one
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * This method will validate a custom rule
	 *
	 * @return boolean
	 */
	public function validateCustomRule(): bool
	{
		return $this->value === $this->expected;
	}

	/**
	 * This method will validate min length of an value
	 *
	 * @param integer $length
	 * @return bool
	 */
	public function validateMinLength(int $length): bool
	{
		// check if is array
		if (is_array($this->value)) {
			// validate array length
			return count($this->value) >= $length;
		}
		// check if is int
		elseif ($this->validateIntType()) {
			// validate 
			return filter_var((int) $this->value, FILTER_VALIDATE_INT, [
				'options' => [
					'min_range' => $length
				]
			]) !== false;
		}
		// check if is float
		elseif ($this->validateFloatType()) {
			// validate 
			return filter_var((float) $this->value, FILTER_VALIDATE_FLOAT, [
				'options' => [
					'min_range' => $length
				]
			]) !== false;
		} else {
			// when is not a string
			if (!is_string($this->value)) {
				return false;
			}

			// validate string length
			return strlen($this->value) >= $length;
		}
	}

	/**
	 * This method will validate max length of an value
	 *
	 * @param integer $length
	 * @return bool
	 */
	public function validateMaxLength(int $length): bool
	{
		// check if is array
		if (is_array($this->value)) {
			// validate array length
			return count($this->value) <= $length;
		}
		// check if is int
		elseif ($this->validateIntType()) {
			// validate 
			return filter_var((int) $this->value, FILTER_VALIDATE_INT, [
				'options' => [
					'max_range' => $length
				]
			]) !== false;
		}
		// check if is float
		elseif ($this->validateFloatType()) {
			// validate 
			return filter_var((float) $this->value, FILTER_VALIDATE_FLOAT, [
				'options' => [
					'max_range' => $length
				]
			]) !== false;
		} else {
			// when is not a string
			if (!is_string($this->value)) {
				return false;
			}

			// validate string length
			return strlen($this->value) <= $length;
		}
	}

	/**
	 * This method will validate string type
	 *
	 * @return boolean
	 */
	public function validateStringType(): bool
	{
		return is_string($this->value);
	}

	/**
	 * This method will validate float type
	 *
	 * @return boolean
	 */
	public function validateFloatType(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_FLOAT) !== false;
	}

	/**
	 * This method will validate int type
	 *
	 * @return boolean
	 */
	public function validateIntType(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * This method will validate array type
	 *
	 * @return boolean
	 */
	public function validateArrayType(): bool
	{
		return is_array($this->value);
	}

	/**
	 * This method will validate regex format
	 *
	 * @return boolean
	 */
	public function validateRegex(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_REGEXP, [
			'options' => [
				'regexp' => '/' . $this->expected . '/'
			]
		]) !== false;
	}

	/**
	 * This method will validate if value is an email
	 *
	 * @return boolean
	 */
	public function validateEmail(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * This method will validate if value is an url
	 *
	 * @return boolean
	 */
	public function validateUrl(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * This method will validate if value is ip adress
	 *
	 * @return boolean
	 */
	public function validateIp(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_IP) !== false;
	}
}
