<?php

namespace tests\Http;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
	protected function setup(): void
	{
		parent::setup();

		$_SERVER['HTTP_HOST'] = 'test.local';
		$_SERVER['REQUEST_URI'] = '/api/test/?test=true';
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	public function testGetHost()
	{
		$this->assertEquals('test.local', request()->server('HTTP_HOST'));
	}

	public function testGetMethod()
	{
		$this->assertEquals('GET', request()->method());
	}

	public function testUri()
	{
		$this->assertEquals('/api/test', request()->uri());
		$this->assertEquals('/api/test/', request()->uri(true));
	}

	public function testUrl()
	{
		$this->assertEquals('/api/test/?test=true', request()->url());
	}

	public function testQueryString()
	{
		$this->assertEquals('test=true', request()->query());
	}

	public function testGetParam()
	{
		$this->assertEquals(null, request()->get('test'));
		$this->assertEquals(null, request('test'));

		$_GET['test'] = 'true';

		$this->assertEquals('true', request()->get('test'));
		$this->assertEquals('true', request('test'));
	}
}
