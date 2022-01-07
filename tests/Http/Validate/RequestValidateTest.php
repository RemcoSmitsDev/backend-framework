<?php

declare(strict_types=1);

namespace Framework\Tests;

use Framework\Http\Validate\CustomRule;
use PHPUnit\Framework\TestCase;

class UniqueEmail extends CustomRule
{
	public function validate($value): bool
	{
		if ($value > 1) {
			$this->message('Dit is een test voor het sturen van een message');

			return false;
		}

		return true;
	}
}

class RequestValidateTest extends TestCase
{
	public function testValidateReturnType()
	{
		$_GET['test'] = '1';

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$validateReturnType = request()->validate([
			'test' => 'required'
		]);

		$this->assertInstanceOf('Framework\Http\Request', $validateReturnType);
	}

	public function testValidateRequired()
	{
		unset($_GET['test']);

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$validateRequired = request()->validate([
			'test' => 'required'
		]);

		$this->assertEquals(true, $validateRequired->failed());

		$_GET['test'] = 'test';

		$validateRequired = request()->validate([
			'test' => 'required'
		]);

		$this->assertEquals(false, $validateRequired->failed());
	}

	public function testValidateOptional()
	{
		unset($_GET['test']);

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$validateOptional = request()->validate([
			'test' => 'int'
		]);

		$this->assertEquals(false, $validateOptional->failed());

		$_GET['test'] = 'asdfjasldkjf';

		$validateOptional = request()->validate([
			'test' => 'int'
		]);

		$this->assertEquals(true, $validateOptional->failed());

		$_GET['test'] = '';

		$validateOptional = request()->validate([
			'test' => 'int'
		]);

		$this->assertEquals(false, $validateOptional->failed());
	}

	public function testValidateStringType()
	{
		unset($_GET['test']);

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['test'] = 'this is an test';

		$validateStringType = request()->validate([
			'test' => ['required', 'string']
		]);

		$this->assertEquals(false, $validateStringType->failed());

		$_GET['test'] = ['this is an test'];

		$validateStringType = request()->validate([
			'test' => ['required', 'string']
		]);

		$this->assertEquals(true, $validateStringType->failed());
	}

	public function testValidateIntType()
	{
		unset($_GET['test']);

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['test'] = 'this is an test';

		$validateIntType = request()->validate([
			'test' => 'int'
		]);

		$this->assertEquals(true, $validateIntType->failed());

		$_GET['test'] = 10;

		$validateIntType = request()->validate([
			'test' => 'int'
		]);

		$this->assertEquals(false, $validateIntType->failed());
	}

	public function testValidateFloatType()
	{
		unset($_GET['test']);

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['test'] = '1.0.10';

		$validateFloatType = request()->validate([
			'test' => 'float'
		]);

		$this->assertEquals(true, $validateFloatType->failed());

		$_GET['test'] = 10;

		$validateFloatType = request()->validate([
			'test' => 'float'
		]);

		$this->assertEquals(false, $validateFloatType->failed());

		$_GET['test'] = 10.1;

		$validateFloatType = request()->validate([
			'test' => 'float'
		]);

		$this->assertEquals(false, $validateFloatType->failed());
	}

	public function testValidateArrayType()
	{
		unset($_GET['test']);

		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['test'] = 'this is an test';

		$validateArrayType = request()->validate([
			'test' => 'array'
		]);

		$this->assertEquals(true, $validateArrayType->failed());

		$_GET['test'] = [
			'this is an test'
		];

		$validateArrayType = request()->validate([
			'test' => 'array'
		]);

		$this->assertEquals(false, $validateArrayType->failed());
	}

	public function testValidateArrayKeyValues()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$validateArrayKeyValues = request()->validate([
			'user' => [
				'name' => ['string', 'min:1'],
				'age' => ['int', 'min:1']
			]
		]);

		$this->assertEquals(false, $validateArrayKeyValues->failed());

		$_GET['user'] = [
			'name' => 'This is an test',
			'age' => 10
		];

		$validateArrayKeyValues = request()->validate([
			'user' => [
				'name' => ['string', 'min:1'],
				'age' => ['int', 'min:11']
			]
		]);

		$this->assertEquals(true, $validateArrayKeyValues->failed());
	}

	public function testValidateMultiDymentionalArray()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['users'] = [
			'name' => 'This is an test',
			'age' => 10
		];

		$validateArrayType = request()->validate([
			'users' => [
				'array' => [
					'name' => ['string', 'min:1'],
					'age' => ['int', 'min:1']
				]
			]
		]);

		$this->assertEquals(true, $validateArrayType->failed());

		$_GET['users'] = [
			[
				'name' => 'This is an test',
				'age' => 10
			]
		];

		$validateArrayType = request()->validate([
			'users' => [
				'array' => [
					'name' => ['string', 'min:1'],
					'age' => ['int', 'min:1']
				]
			]
		]);

		$this->assertEquals(false, $validateArrayType->failed());
	}

	public function testValidateMin()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10
		];

		$validate = request()->validate([
			'user' => [
				'required',
				'name' => ['min:1'],
				'age' => ['min:1'],
			]
		]);

		$this->assertEquals(false, $validate->failed());

		$validate = request()->validate([
			'user' => [
				'required',
				'name' => ['string', 'min:9'],
				'age' => ['int', 'min:11'],
			]
		]);

		$this->assertEquals(true, $validate->failed());
	}

	public function testValidateRegex()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest'
		];

		$validate = request()->validate([
			'user' => [
				'password' => 'regex:[0-9]+'
			]
		]);

		$this->assertEquals(true, $validate->failed());

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest0-9'
		];

		$validate = request()->validate([
			'user' => [
				'password' => 'regex:^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$'
			]
		]);

		$this->assertEquals(false, $validate->failed());
	}

	public function testValidateEmail()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest',
			'email' => 'test@example.com'
		];

		$validate = request()->validate([
			'user' => [
				'email' => 'email'
			]
		]);

		$this->assertEquals(false, $validate->failed());

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest',
			'email' => 'test@example'
		];

		$validate = request()->validate([
			'user' => [
				'email' => 'email'
			]
		]);

		$this->assertEquals(true, $validate->failed());
	}

	public function testValidateUrl()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest',
			'email' => 'test@example.com',
			'site' => 'https://www.google.com'
		];

		$validate = request()->validate([
			'user' => [
				'site' => 'url'
			]
		]);

		$this->assertEquals(false, $validate->failed());

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest',
			'email' => 'test@example.com',
			'site' => 'www.google.com'
		];

		$validate = request()->validate([
			'user' => [
				'site' => 'url'
			]
		]);

		$this->assertEquals(true, $validate->failed());
	}

	public function testValidateIp()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest',
			'email' => 'test@example.com',
			'site' => 'https://www.google.com',
			'ip' => '192.178.0.1'
		];

		$validate = request()->validate([
			'user' => [
				'ip' => 'ip'
			]
		]);

		$this->assertEquals(false, $validate->failed());

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest',
			'email' => 'test@example.com',
			'site' => 'www.google.com',
			'ip' => '192.178.0.1:8080'
		];

		$validate = request()->validate([
			'user' => [
				'ip' => 'ip'
			]
		]);

		$this->assertEquals(true, $validate->failed());
	}

	public function testValidateCustomRule()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$_GET['user'] = [
			'name' => 'This is a test',
			'age' => 10,
			'password' => 'ThisIsAnTest',
			'email' => 'test@example.com',
			'site' => 'https://www.google.com',
			'userId' => 2
		];

		$validate = request()->validate([
			'user' => [
				'userId' => UniqueEmail::class
			]
		]);

		$this->assertEquals(true, $validate->failed());
	}
}
