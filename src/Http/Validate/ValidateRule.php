<?php

namespace Framework\Http\Validate;


class ValidateRule
{
	/**
	 * Keeps track of all rules with function name to call
	 *
	 * @var array
	 */
	protected array $rules = [
		'string' => 'validateStringType',
		'int' => 'validateIntType',
		'float' => 'validateFloatType',
		'array' => 'validateArrayType',
		'min' => 'validateMinLength',
		'max' => 'validateMaxLength',
		'regex' => 'validateRegex',
		'email' => 'validateEmail',
		'url' => 'validateUrl',
		'ip' => 'validateIp',
		'custom' => 'validateCustomRule'
	];

	/**
	 * This will setup the needed data
	 *
	 * @param string $type The validation type(min|max|string|float|email)
	 * @param string $expected The expected value
	 * @param boolean $required If the rules need to be required
	 * @param mixed $value Is the value that the rule need to apply to
	 */
	public function __construct(
		protected string $type,
		protected string $expected,
		protected bool $required,
		protected mixed $value,
	) {
	}

	public function validate()
	{
		// keep track of function to call
		$functionToCall = $this->rules['custom'];
		// args to apply
		$args = [];

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

		// call function
		return call_user_func([$this, $functionToCall], ...$args);
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
			return filter_var($this->value, FILTER_VALIDATE_INT, [
				'options' => [
					'min_range' => $length
				]
			]);
		}
		// check if is float
		elseif ($this->validateFloatType()) {
			// validate 
			return filter_var($this->value, FILTER_VALIDATE_FLOAT, [
				'options' => [
					'min_range' => $length
				]
			]);
		} else {
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
			return filter_var($this->value, FILTER_VALIDATE_INT, [
				'options' => [
					'max_range' => $length
				]
			]);
		}
		// check if is float
		elseif ($this->validateFloatType()) {
			// validate 
			return filter_var($this->value, FILTER_VALIDATE_FLOAT, [
				'options' => [
					'max_range' => $length
				]
			]);
		} else {
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
		]);
	}

	/**
	 * This method will validate if value is an email
	 *
	 * @return boolean
	 */
	public function validateEmail(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * This method will validate if value is an url
	 *
	 * @return boolean
	 */
	public function validateUrl(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_URL);
	}

	/**
	 * This method will validate if value is ip adress
	 *
	 * @return boolean
	 */
	public function validateIp(): bool
	{
		return filter_var($this->value, FILTER_VALIDATE_IP);
	}
}
