<?php

declare(strict_types=1);

namespace Framework\Tests\Container;

use Framework\Container\Container;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Framework\Http\Request;

class ClosureTest
{
	public function __construct(
		public Request $request,
		public string $name
	) {
	}

	public function test(string $name, $test = null): ClosureTest
	{
		return $this;
	}
}

class ContainerTest extends TestCase
{
	public function test_handle_class_method_response()
	{
		$response = Container::handleClassMethod(ClosureTest::class, 'test', ['name' => 'test']);

		$this->assertInstanceOf(ClosureTest::class, $response);

		$this->assertInstanceOf(Request::class, $response->request);
		$this->assertEquals('test', $response->name);
	}

	public function test_handle_class_method_missing_arguments_exception()
	{
		$this->expectException(InvalidArgumentException::class);

		Container::handleClassMethod(ClosureTest::class, 'test');
	}

	public function test_handle_private_function()
	{
		$closure = function (ClosureTest $closureTest) {
			return $closureTest;
		};

		$response = Container::handleClosure($closure, ['name' => 'test1']);

		$this->assertInstanceOf(ClosureTest::class, $response);
		$this->assertEquals('test1', $response->name);

		$closure = function (ClosureTest $closureTest, string $test) {
			return $closureTest;
		};

		$response = Container::handleClosure($closure, ['name' => 'test1', 'test' => 'this is a test string']);

		$this->assertInstanceOf(ClosureTest::class, $response);
		$this->assertEquals('test1', $response->name);

		$closure = function (ClosureTest $closureTest, string $test = 'default') {
			return $closureTest;
		};

		$response = Container::handleClosure($closure, ['name' => 'test1']);

		$this->assertInstanceOf(ClosureTest::class, $response);
	}

	public function test_handle_private_function_missing_param()
	{
		$this->expectException(InvalidArgumentException::class);

		$closure = function (ClosureTest $closureTest, string $test) {
			return $closureTest;
		};

		Container::handleClosure($closure, ['name' => 'test1']);
	}
}
