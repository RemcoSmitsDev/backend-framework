<?php

declare(strict_types=1);

namespace Framework\Support\Contracts;

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray();
}
