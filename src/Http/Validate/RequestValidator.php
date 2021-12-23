<?php

declare(strict_types=1);

namespace Framework\Http\validate;

use Framework\Http\Validate\ValidateRule;

class RequestValidator
{
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

	/**
	 * Keeps track if value need to exists on request
	 *
	 * @var boolean
	 */
	private bool $required = false;

	/**
	 * Keeps track of failed status
	 *
	 * @var boolean
	 */
	private bool $failed = false;

	/**
	 * Keeps track of which request data to send back
	 *
	 * @var array
	 */
	private array $returnData = [];

	/**
	 * This will keep track of key was foun inside request
	 *
	 * @var boolean
	 */
	private bool $foundData = false;

	/**
	 * This method will loop recursive through all rules
	 *
	 * @param array $rules
	 * @param string|integer $key
	 * @param mixed $value
	 * @return void
	 */
	private function recursiveLoop(array $rules, mixed $value)
	{
		// loop trough all rules
		foreach ($rules as $key => $rule) {

			// when [
			// 'name' => 'string'
			// ]
			if (is_int($key) && is_string($rule)) {
				// validate rule
				$this->validateRule($rule, $key, $value);

				// force to go to the next in the array
				continue;
			}
			// when rule is multidimensional array rule
			// 'users' => [
			//  0 => [
			//    'name' => 'test'
			// 	],
			//  1 => [
			//    'name' => 'test'
			// 	]
			// ]
			elseif ($key === 'array' && is_array($rule)) {
				// check if array is isMultidimensional
				if (!isMultidimensional($value)) {
					// add failed rule to array
					$this->failedRules[] = [
						'rule' => $rule,
						'key' => $key,
						'value' => $value
					];

					// set failed to true
					$this->setFailed(true);

					// force to got to the next in the array
					continue;
				}

				// loop trough multidimensional array an validate rules
				foreach ($value as $val) {
					$this->recursiveLoop($rule, $val);
				}

				// force to go to the next in the array
				continue;
			}
			// when rule key is string and has array of rules
			elseif (is_string($key) && is_array($rule)) {

				// check if key exists inside value
				if (!array_key_exists($key, (array) $value)) {
					// add to failed rules
					$this->failedRules[] = [
						'rule' => $rule,
						'key' => $key,
						'value' => $value
					];

					// set failed to true
					$this->setFailed(true);
				} else {
					$this->recursiveLoop($rule, $value[$key]);
				}

				// force to go to the next one
				continue;
			}
			// when is normal validation rule
			else {

				// check if key exists inside value
				if (!array_key_exists($key, (array) $value)) {
					// added to failed rules
					$this->failedRules[] = [
						'rule' => $rule,
						'key' => $key,
						'value' => $value
					];

					// set failed to true
					$this->setFailed(true);

					// go to the nex rule
					continue;
				}

				// validate rule
				$this->validateRule($rule, $key, $value[$key]);
			}
		}
	}

	/**
	 *
	 * @param array $rules
	 * @return bool|array
	 */
	public function validate(array $rules): bool|array
	{
		// loop trough all rules
		foreach ($rules as $key => $rule) {
			// find required inside rules array
			$requiredIndex = array_search('required', (array) $rule);

			// set required status
			$this->required = $requiredIndex !== false;

			// when required was found remove from rule(s)
			if ($this->required && is_array($rule)) {
				unset($rule[$requiredIndex]);
			} elseif ($this->required && is_string($rule)) {
				continue;
			}

			// set found data
			$this->foundData = request()->exists($key);

			// loop recursive through all rules
			$this->recursiveLoop((array) $rule, request($key));

			// check if rule failed
			if (!$this->failed) {
				// add to return data
				$this->returnData[$key] = request($key);
			}
		}

		// return bool when failed return data when passed
		return $this->failed() ? false : $this->returnData;
	}

	/**
	 * This method will validate a rule with the value that need to be validated
	 *
	 * @param string $rule
	 * @param string|int $key
	 * @param mixed $value
	 * @return void
	 */
	private function validateRule(string $rule, string|int $key, mixed $value)
	{
		// validate rule
		$validateRule = new ValidateRule(
			type: preg_replace('/:(.+)|\s+/', '', $rule),
			expected: $rule,
			required: $this->required,
			value: $value
		);

		// validate rule
		$passed = $validateRule->validate();

		// check if validation passed
		if (!$passed) {
			// added to failed rules
			$this->failedRules[] = [
				'rule' => $rule,
				'key' => $key,
				'value' => $value
			];

			// set failed to true
			$this->setFailed(true);
		} else {
			// add to passed rules
			$this->passedRules[] = [
				'rule' => $rule,
				'key' => $key,
				'value' => $value
			];
		}
	}

	/**
	 * This method will return if the validation passed
	 *
	 * @return boolean
	 */
	public function failed(): bool
	{
		return $this->failed;
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

	/**
	 * sets failed status
	 *
	 * @param boolean $failed
	 */
	private function setFailed(bool $failed)
	{
		// when rule is optional and data was found an rule failed set status to failed
		if (!$this->required && $this->foundData && $failed) {
			// set failed status
			$this->failed = true;

			// return
			return false;
		}

		// stop when is optional
		// stop when is alreay failed
		if (!$this->required || $this->failed) {
			return false;
		}

		// set status
		$this->failed = $failed;
	}
}
