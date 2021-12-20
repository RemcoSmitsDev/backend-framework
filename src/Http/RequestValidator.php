<?php

declare(strict_types=1);

namespace Framework\Http;

class RequestValidator
{
	/**
	 * All validation rules
	 */
	const VALIDATE_RULES = [
		'bool' => FILTER_VALIDATE_BOOLEAN,
		'float' => FILTER_VALIDATE_FLOAT,
		'int' => FILTER_VALIDATE_INT,
		'string',
		'email' => FILTER_VALIDATE_EMAIL,
		'url' => FILTER_VALIDATE_URL,
		'regex' => FILTER_VALIDATE_REGEXP,
		'min' => 0,
		'max' => INF,
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
			} elseif (is_array($rule)) {
				$rule = flattenArray($rule);
			}

			// find required inside rules array
			$requiredIndex = array_search('required', $rule);

			// set required status
			$this->required = $requiredIndex !== false;

			// when required was found remove from rule(s)
			if ($this->required) {
				unset($rule[$requiredIndex]);
			}

			// when has only required rule
			if (empty($rule)) {
				// set passed rules
				$this->passedRules[$key] = [
					'rule' => $rule,
					'key' => $key,
					'value' => request($key),
				];

				// add to return data
				$this->returnData[$key] = request($key);
			}

			// loop trough all rules
			foreach ($rule as $r) {
				// validate rule
				$this->validateRule(
					$key,
					$r,
					!isset(self::VALIDATE_RULES[preg_replace('/:(.+)|\s+/', '', $r)])
				);
			}
		}

		// return bool when failed return data when passed
		return $this->failed ? false : $this->returnData;
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
		elseif (preg_match('/^(max|min):([0-9]+)/', $rule, $match)) {
			// check if passed validation
			$failed = !$this->validateStringLength($match[1], intval($match[2]), $key);

			// update rule
			$rule = $match[1];

			// expected value
			$expected = ($rule === 'max' ? ' < ' : ' > ') . $match[2];
		}
		// when is regex rule
		elseif (preg_match('/^regex:(.+)/', $rule, $match)) {
			// check if regex match failed
			$failed = !filter_var(request($key), FILTER_VALIDATE_REGEXP, [
				'options' => [
					'regexp' => '/' . $match[1] . '/'
				]
			]);

			// set rule name
			$rule = 'regex';

			// expected value
			$expected = 'Regex match: ' . $match[1];
		}
		// when rule must be an type of string
		elseif ($rule === 'string') {
			// check if is string
			$failed = !is_string(request($key));
		}
		// when is normal filter_var validation
		else {
			// check if validatin failed
			$failed = !filter_var(request($key), self::VALIDATE_RULES[$rule]);

			// set expected value
			$expected = ' typeof ' . $key;
		}

		// set failed status
		$this->setFailed($failed);

		// check if rule failed
		if ($this->required && $failed) {
			// add to failed value
			$this->failedRules[$key] = [
				'rule' => $rule,
				'key' => $key,
				'value' => request($key),
				'expected' => $expected
			];
		} else {
			// set passed rules
			$this->passedRules[$key] = [
				'rule' => $rule,
				'key' => $key,
				'value' => request($key),
			];

			// add to return data
			$this->returnData[$key] = request($key);
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
		// get value from request
		$value = request($key);

		// check if value is numeric
		if (($value = filter_var($value, FILTER_SANITIZE_NUMBER_INT)) !== false) {
			// validate
			return filter_var($value, FILTER_VALIDATE_INT, ['options' => [
				$type . '_range' => $length
			]]);
		} elseif (($value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT)) !== false) {
			// validate
			return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, ['options' => [
				$type . '_range' => $length
			]]);
		} else {
			// when type is min
			if ($type === 'min') {
				return strlen(request($key)) >= $length;
			}
			// validate
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

	/**
	 * sets failed status
	 *
	 * @param boolean $failed
	 */
	private function setFailed(bool $failed)
	{
		// stop when is optional
		// stop when is alreay failed
		if (!$this->required || $this->failed) {
			return false;
		}

		// set status
		$this->failed = $failed;
	}
}
