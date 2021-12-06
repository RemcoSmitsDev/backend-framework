<?php

namespace Framework\Parallel;


class ParallelTask
{
    public function __construct(private $callback)
    {
    }

    public function getClosure()
    {
        return $this->callback;
    }
}
