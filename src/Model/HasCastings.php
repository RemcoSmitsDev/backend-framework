<?php

declare(strict_types=1);

namespace Framework\Model;

trait HasCastings
{
    /**
     * @return array<string, Closure>
     */
    protected function castings(): array
    {
        return [];
    }

    /**
     * @param  mixed  $value
     * @param  string $key
     *
     * @return mixed
     */
    protected function handleCast(string $key, mixed $value): mixed
    {
        if (!isset(static::castings()[$key]) || !is_callable(static::castings()[$key])) return $value;

        return call_user_func(static::castings()[$key], $value);
    }
}
