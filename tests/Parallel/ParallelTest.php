<?php

declare(strict_types=1);

namespace Framework\Tests;

use Framework\Parallel\Parallel;
use PHPUnit\Framework\TestCase;

class ParallelTest extends TestCase
{
    public function testParallelResultEquals()
    {
        $parallel = new Parallel();

        $result = $parallel->run(function () {
            sleep(3);
            return 1;
        }, function () {
            sleep(2);
            return 0;
        });

        $this->assertEquals([1, 0], $result);
    }
}
