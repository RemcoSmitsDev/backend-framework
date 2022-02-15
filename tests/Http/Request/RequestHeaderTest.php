<?php

namespace test\Http\Request;

use Framework\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestHeaderTest extends TestCase
{
    public function testGetHeader()
    {
        $_SERVER['Test_Header'] = 'test';

        app()->setInstance(new Request());

        $this->assertEquals('test', request()->headers('Test_Header'));

        $this->assertEquals(null, request()->headers('Test_Header_Test'));

        $this->assertEquals('test', request()->headers('Test_Header_Test', 'test'));
    }

    public function testOverWriteHeader()
    {
        $_SERVER['Test_Header'] = 'test';

        app()->setInstance(new Request());

        $this->assertEquals('test', request()->headers('Test_Header'));

        request()->headers()->Test_Header = 'test1';

        $this->assertEquals('test1', request()->headers('Test_Header'));
    }
}
