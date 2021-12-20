<?php

declare(strict_types=1);

namespace Framework\Tests;

use Framework\Debug\Ray;
use PHPUnit\Framework\TestCase;

class RayTest extends TestCase
{
	/* @test */
	public function testSetsTitle()
	{
		$ray = new Ray();

		$ray->title('test');

		$this->assertEquals('test', 'tests');
	}
}
