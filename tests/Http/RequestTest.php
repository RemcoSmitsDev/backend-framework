<?php

namespace tests\Http;

use Framework\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['REQUEST_URI'] = '/api/test/?test=true';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        app()->setInstance(new Request());
    }

    public function testGetHost()
    {
        $this->assertEquals('test.local', request()->server('HTTP_HOST'));
    }

    public function testMethod()
    {
        $this->assertEquals('GET', request()->method());

        $_SERVER['REQUEST_METHOD'] = 'POST';

        app()->setInstance(new Request());

        $this->assertEquals('POST', request()->method());
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

        app()->setInstance(new Request());

        $this->assertEquals('true', request()->get('test'));
        $this->assertEquals('true', request('test'));
    }
}
