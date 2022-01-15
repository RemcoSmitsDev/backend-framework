<?php

declare(strict_types=1);

namespace Framework\Tests\Container;

use Framework\Container\Container;
use PHPUnit\Framework\TestCase;

class ClosureTest
{
	public function test(ClosureTest $closureTest, string $name)
	{
	}
}

class ContainerTest extends TestCase
{
	public function testHandleClosureParams()
	{
		$closure = function (ClosureTest $closureTest) {
		};

		$params = Container::handleClosure($closure);

		$this->assertInstanceOf(ClosureTest::class, reset($params));
	}

	public function testHandleClassMethodParams()
	{
		$params = Container::handleClassMethod(ClosureTest::class, 'test', ['name' => 'askdfjlksadjflksdj']);

		$this->assertEquals([new ClosureTest, 'askdfjlksadjflksdj'], $params);
	}
}
