<?php

declare(strict_types=1);

namespace Framework\Http\Validate;

class RequestValidator
{
    /**
     * keep track of failed rules.
     *
     * @var array
     */
    private array $failedRules = [];

    /**
     * Keep track of passed rules.
     *
     * @var array
     */
    private array $passedRules = [];

    /**
     * Keeps track if value need to exists on request.
     *
     * @var bool
     */
    private bool $required = false;

    /**
     * Keeps track of failed status.
     *
     * @var bool
     */
    private bool $failed = false;

    /**
     * Keeps track of which request data to send back.
     *
     * @var array
     */
    private array $returnData = [];

    /**
     * This will keep track of key was foun inside request.
     *
     * @var bool
     */
    private bool $foundData = false;

    /**
     * This will keep track of the request key.
     *
     * @var string|null
     */
    private ?string $key = null;

    /**
     * This will keep track of error messages.
     *
     * @var array
     */
    private array $errorMessages = [];

    /**
     * This method will loop recursive through all rules.
     *
     * @param array      $rules
     * @param string|int $key
     * @param mixed      $value
     *
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
                // when array must be multidimensional
                if (is_string(array_key_first((array) $rule)) && !isMultidimensional($value) || !is_array($value)) {
                    // add failed rule to array
                    $this->addToFailedRules($rule, $key, $value);

                    // append to messages array
                    $this->errorMessages[] = "`{$this->key}` zou een reeks of waardes moeten zijn.";

                    // force to got to the next in the array
                    continue;
                }

                // keep track of old key
                $_key = $this->key;

                // loop trough multidimensional array an validate rules
                foreach ($value as $key => $val) {
                    // update key
                    $this->key = "{$_key}.{$key}";

                    // loop recursive
                    $this->recursiveLoop((array) $rule, $val);
                }

                // reset key
                $this->key = $_key;

                // force to go to the next in the array
                continue;
            }
            // when rule key is string and has array of rules
            elseif (is_string($key) && is_array($rule)) {
                // check if key exists inside value
                if (!array_key_exists($key, (array) $value)) {
                    // add to failed rules
                    $this->addToFailedRules($rule, $key, $value);

                    // append to messages array
                    $this->errorMessages[] = "`{$this->key}` kon niet gevonden worden.";
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
                    $this->addToFailedRules($rule, $key, $value);

                    // check if key is an string
                    if (is_string($key)) {
                        // set key
                        $this->key .= '.'.$key;
                    }

                    // append to messages array
                    $this->errorMessages[] = "`{$this->key}` kon niet gevonden worden.";

                    // go to the nex rule
                    continue;
                }

                // validate rule
                $this->validateRule($rule, $key, $value[$key]);
            }
        }
    }

    /**
     * @param array $rules
     *
     * @return self
     */
    public function validate(array $rules): self
    {
        // reset values
        $this->reset();

        // loop trough all rules
        foreach ((array) $rules as $key => $rule) {
            // force rule to be array
            $rule = (array) $rule;

            // find required inside rules array
            $requiredIndex = array_search('required', $rule);

            // set key
            $this->key = $key;

            // set required status
            $this->required = $requiredIndex !== false;

            // when required was found remove from rule(s)
            if ($this->required && is_array($rule)) {
                unset($rule[$requiredIndex]);
            } elseif ($this->required && is_string($rule) && count($rule) === 1) {
                // add to return data
                $this->returnData[$key] = request($key);

                // force to go to the next rule
                continue;
            }

            // set found data
            $this->foundData = request()->exists($key);

            // when there is no data found but rule is required
            if (!$this->foundData && $this->required) {
                // add to failed rules
                $this->addToFailedRules($rule, $key, null);

                // append to messages array
                $this->errorMessages[] = "`{$this->key}` kon niet gevonden worden.";

                continue;
            }

            // loop recursive through all rules
            $this->recursiveLoop($rule, request($key));

            // check if rule failed
            if (!$this->failed) {
                // add to return data
                $this->returnData[$key] = request($key);
            }
        }

        // return bool when failed return data when passed
        return $this;
    }

    /**
     * This method will validate a rule with the value that need to be validated.
     *
     * @param string     $rule
     * @param string|int $key
     * @param mixed      $value
     *
     * @return void
     */
    private function validateRule(string $rule, string|int $key, mixed $value)
    {
        // validate rule
        $validateRule = new ValidateRule(
            rule: preg_replace('/:(.+)|\s+/', '', $rule),
            key: $this->key,
            expected: $rule,
            required: $this->required,
            value: $value
        );

        // validate rule
        $passed = $validateRule->validate();

        // check if validation passed
        if (!$passed) {
            // append to error messages
            $this->errorMessages[] = $validateRule->getMessage();

            // added to failed rules
            $this->addToFailedRules($rule, $this->key, $value);
        } else {
            // add to passed rules
            $this->passedRules[] = [
                'rule'  => $rule,
                'key'   => $key,
                'value' => $value,
            ];
        }
    }

    /**
     * This will add a failed rule to the failed rules array.
     *
     * @param mixed      $rule
     * @param string|int $key
     * @param mixed      $value
     *
     * @return void
     */
    public function addToFailedRules(mixed $rule, string|int $key, mixed $value): void
    {
        // add failed rule to array
        $this->failedRules[] = [
            'rule'  => $rule,
            'key'   => $key,
            'value' => $value,
        ];

        // check if is optional and value is empty
        if (!$this->required && empty($value)) {
            return;
        }

        // set failed to true
        $this->setFailed(true);
    }

    /**
     * This method will return if the validation passed.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->failed;
    }

    /**
     * This method will get all that that passed the rules.
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->returnData;
    }

    /**
     * Return all errors/failed.
     *
     * @return array
     */
    public function getFailedRules(): array
    {
        // keep track of errrors
        return $this->failedRules;
    }

    /**
     * Returns all error messages.
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Undocumented function.
     *
     * @return array
     */
    public function getPassedRules(): array
    {
        return $this->passedRules;
    }

    /**
     * sets failed status.
     *
     * @param bool $failed
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
        if (!$this->required || $this->failed()) {
            return false;
        }

        // set status
        $this->failed = $failed;
    }

    /**
     * This method will reset all property values.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->failedRules = [];
        $this->passedRules = [];
        $this->required = false;
        $this->failed = false;
        $this->returnData = [];
        $this->foundData = false;
        $this->key = null;
        $this->errorMessages = [];
    }
}
