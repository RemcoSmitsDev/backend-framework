<?php

namespace tests\Http;

use Framework\App;
use Framework\Http\Api;
use Framework\Http\Request;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        new App();
    }

    public function testIsFromAjax()
    {
        $this->assertFalse(Api::fromAjax());

        $_SERVER['X-REQUESTED-WITH'] = 'xmlhttprequest';

        app()->setInstance(new Request());

        $this->assertTrue(Api::fromAjax());
    }

    public function testFromOwnServer()
    {
        $this->assertFalse(Api::fromOwnServer());

        $_SERVER['HTTP_REFERER'] = 'test.local';

        app()->setInstance(new Request());

        $this->assertTrue(Api::fromOwnServer());

        $_SERVER['HTTP_REFERER'] = 'test2.local';

        app()->setInstance(new Request());

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

        app()->setInstance(new Request());

        $this->assertTrue(Api::validateToken());

        $_SERVER['Requesttoken'] = $token.'a';

        app()->setInstance(new Request());

        $this->assertFalse(Api::validateToken());

        unset($_SERVER['Requesttoken']);

        app()->setInstance(new Request());

        $this->assertFalse(Api::validateToken());
    }
}
