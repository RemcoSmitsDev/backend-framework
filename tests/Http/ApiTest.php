<?php

namespace tests\Http;

use Framework\Http\Api;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
	protected function setup(): void
	{
		parent::setup();

		$_SERVER['HTTP_HOST'] = 'test.local';
		$_SERVER['REQUEST_URI'] = '/api/test';
	}

	public function testIsFromAjax()
	{
		$this->assertFalse(Api::fromAjax());

		$_SERVER['X-REQUESTED-WITH'] = 'xmlhttprequest';

		$this->assertTrue(Api::fromAjax());
	}

	public function testFromOwnServer()
	{
		$this->assertFalse(Api::fromOwnServer());

		$_SERVER['HTTP_REFERER'] = 'test.local';

		$this->assertTrue(Api::fromOwnServer());

		$_SERVER['HTTP_REFERER'] = 'test2.local';

		$this->assertFalse(Api::fromOwnServer());
	}

	public function testValidateToken()
	{
		$token = Api::generateRequestToken();

		$this->assertTrue(isset($_COOKIE['requestToken']));

		$this->assertEquals($token, $_COOKIE['requestToken']);

		$_SESSION['requestToken'] = $token;

		$this->assertFalse(Api::validateToken());

		$_SERVER['Requesttoken'] = $token;

		$this->assertTrue(Api::validateToken());

		$_SERVER['Requesttoken'] = $token . 'a';

		$this->assertFalse(Api::validateToken());

		unset($_SERVER['Requesttoken']);

		$this->assertFalse(Api::validateToken());
	}
}
