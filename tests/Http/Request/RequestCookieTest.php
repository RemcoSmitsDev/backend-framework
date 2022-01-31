<?php

namespace tests\Http\Request;

use Framework\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestCookieTest extends TestCase
{
	public function testGetCookie()
	{
		$_COOKIE['test'] = 'test';

		$this->assertEquals(null, request()->cookies('test'));

		$this->assertEquals('test', request()->cookies('test', 'test'));

		$_SERVER['Cookie'] = 'PHPSESSID=u30vn0lgpmf6010ro4ol9snle1; test=test1';

		app()->setInstance(new Request);

		$this->assertEquals('test1', request()->cookies('test'));

		$this->assertEquals('test1', request()->cookies('test2', 'test1'));
	}
}
