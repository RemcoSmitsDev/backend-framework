<?php

declare(strict_types=1);

namespace Framework\Http\Validate;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
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
