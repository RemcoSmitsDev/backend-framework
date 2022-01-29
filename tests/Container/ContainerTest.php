<?php

declare(strict_types=1);

namespace Framework\Tests\Container;

use Framework\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConstructorTest
{
	public function __construct(
		private ClosureTest $closureTest
	) {
	}

	public function testWithConstructor(ClosureTest $closureTest)
	{
	}

	private function testPrivateMethodException(ClosureTest $closureTest)
	{
	}
}

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

	public function testHandleClassMethodParamsException()
	{
		$this->expectException(InvalidArgumentException::class);

		$params = Container::handleClassMethod(ClosureTest::class, 'test', ['name' => 'askdfjlksadjflksdj', 'test' => '1', 'b' => '2']);

		$this->assertEquals([new ClosureTest, 'askdfjlksadjflksdj'], $params);
	}

	public function testClassMethodWithConstructor()
	{
		$params = Container::handleClassMethod(ConstructorTest::class, 'testWithConstructor');

		$this->assertArrayHasKey(0, $params);

		$this->assertEquals([new ClosureTest], $params);
	}

	public function testPrivateMethod()
	{
		$this->expectException(\ReflectionException::class);

		Container::handleClassMethod(ConstructorTest::class, 'testPrivateMethodException');
	}
}
