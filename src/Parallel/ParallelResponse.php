<?php

namespace Framework\Parallel;

use function Opis\Closure\{serialize as s, unserialize as u};

class ParallelResponse
{
    public function __construct(public $response)
    {
    }

    public function getResponse()
    {
        return $this->response;
    }
}
