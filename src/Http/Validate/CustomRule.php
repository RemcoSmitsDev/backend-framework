<?php

namespace Framework\Http\Validate;

abstract class CustomRule
{
    /**
     * This will keep track of the message for when the rule didn't passed.
     *
     * @var string|null
     */
    protected ?string $message = null;

    /**
     * This method needs to return boolean(true: when the rule did pass. false: when the rule didn't pass)
     * This method needs also sets the message for when the rule didn't passed.
     *
     * @return bool
     */
    abstract public function validate(mixed $value): bool;

    /**
     * This method will set the message for when the rule didn't passed.
     *
     * @param string $message
     *
     * @return void
     */
    protected function message(string $message): void
    {
        $this->message = $message;
    }

    /**
     * This message with return the message that was set.
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }
}
