<?php

declare(strict_types=1);

namespace Framework\Support\Helpers;

use Closure;

final class Arr
{
    /**
     * @param  array        $array
     * @param  Closure|null $callback
     *
     * @return mixed
     */
    public static function first(array $array, ?Closure $callback = null): mixed
    {
        foreach ($array as $key => $item) {
            // when there is no callback function
            if (is_null($callback)) {
                return $item;
            }

            // when callback returns true
            if ($callback($item, $key)) {
                return $item;
            }
        }

        return false;
    }

    /**
     * @param  array        $array
     * @param  Closure|null $callback
     *
     * @return mixed
     */
    public static function last(array $array, ?Closure $callback = null): mixed
    {
        return self::first(
            $this->all()
            array_reverse($this->all(), true)
        );
    }

    /**
     * @param  array  $array
     *
     * @return  array
     */
    public static function flatten(array $array): array
    {
        return array_reduce($array, function ($array, $item) {
            // merge flatten array with new value
            return array_merge($array, is_array($item) ? flattenArray($item) : [$item]);
        }, []);
    }

    /**
     * @param array
     *
     * @return bool
     */
    public static function isMultidimensional(array $array): bool
    {
        return is_array($value) && is_array($value[array_key_first($value)]);
    }

    /**
     * @param  array     $array
     * @param  ...string $without
     *
     * @return array
     */
    public static function without(array $array, string ...$without): array
    {
        foreach ($array as $key => $value) {
            if (in_array($key, $without)) {
                unset($data[$key]);
                continue;
            }

            if (is_array($value)) {
                $value = self::without($value);
            }
        }

        return $array;
    }

    /**
     * @param  array     $array
     * @param  ...string $except
     *
     * @return array
     */
    public static function except(array $array, string ...$except): array
    {
        return self::without(
            $array,
            ...array_filter(array_keys($array), fn ($key) => !in_array($key, $except))
        );
    }
}
