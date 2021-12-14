<?php

namespace Framework\Http;

class RequestValidator extends Request
{
	/**
	 * All validation rules
	 */
	const VALIDATE_RULES = [
		'bool' => FILTER_VALIDATE_BOOLEAN,
		'float' => FILTER_VALIDATE_FLOAT,
		'int' => FILTER_VALIDATE_INT,
		'email' => FILTER_VALIDATE_EMAIL,
		'url' => FILTER_VALIDATE_URL,
		'regex' => FILTER_VALIDATE_REGEXP,
		'min' => 0,
		'max' => INF
	];

	/**
	 * keep track of failed rules
	 *
	 * @var array
	 */
	private array $failedRules = [];

	/**
	 * Keep track of passed rules
	 *
	 * @var array
	 */
	private array $passedRules = [];


	public function __construct()
	{
		// call parent constructor
		parent::__construct();
	}

	/**
	 *
	 * @param array $rules
	 * @return bool|array
	 */
	public function validate(array $rules): bool|array
	{
		// loop through all rules
		foreach ($rules as $key => $rule) {
			// check if rules exists else it need to be this value
			if (is_string($rule)) {
				$rule = explode('|', $rule);
			} elseif (!is_array($rule)) {
				$rule = [$rule];
			}
			// loop trough all rules
			foreach ($rule as $r) {
				$this->validateRule($key, $r, !isset(self::VALIDATE_RULES[preg_replace('/:[0-9]+|\s+/', '', $r)]));
			}
		}

		// return bool when failed return data when passed
		return $this->failedRules ? false : $this->passedRules;
	}

	/**
	 *
	 * @param string $key
	 * @param string $rule
	 * @param boolean $isCustomRule
	 * @return void
	 */
	private function validateRule(string $key, mixed $rule, bool $isCustomRule = false)
	{
		// check if is 
		if ($isCustomRule) {
			// check if validation failed
			$failed = request($key) !== $rule;

			// set expected value
			$expected = $rule;
		}
		// validate min/max string length
		elseif (preg_match('/(max|min):([0-9]+)/', $rule, $match)) {
			// check if passed validation
			$failed = !$this->validateStringLength($match[1], intval($match[2]), $key);

			// update rule
			$rule = $match[1];

			// expected value
			$expected = ($rule === 'max' ? ' < ' : ' > ') . $match[2];
		}
		// when is normal filter_var validation
		else {
			// check if validatin failed
			$failed = !filter_var(request($key), self::VALIDATE_RULES[$rule]);

			// set expected value
			$expected = ' typeof ' . $key;
		}

		// check if rule failed
		if ($failed) {
			// add to failed value
			$this->failedRules[$key] = [
				'rule' => $rule,
				'key' => $key,
				'value' => request($key),
				'expected' => $expected
			];
		} else {
			$this->passedRules[$key] = [
				'rule' => $rule,
				'key' => $key,
				'value' => request($key),
			];
		}
	}

	/**
	 * @param string $type (min|max)
	 * @param string $key
	 * @param [type] $rule
	 * @return void
	 */
	private function validateStringLength(string $type, int $length, string $key)
	{
		if ($type === 'min') {
			return strlen(request($key)) >= $length;
		} else {
			return strlen(request($key)) <= $length;
		}
	}

	/**
	 * Return all errors/failed
	 * @return array
	 */
	public function getErrors(): array
	{
		// keep track of errrors
		return $this->failedRules;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public function getPassedRules(): array
	{
		return $this->passedRules;
	}
}
