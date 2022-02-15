<?php

namespace Framework\Database\QueryBuilder\RawQuery;

use Framework\Database\QueryBuilder\QueryBuilder;

class RawQuery
{
    public function __construct(
        private QueryBuilder $builder,
        private string $query
    ) {
    }
}
