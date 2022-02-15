<?php

namespace tests\Http\Request;

use Framework\Http\Request;
use PHPUnit\Framework\TestCase;

class ValidateCsrfTokenTest extends TestCase
{
    public function testGenerateToken()
    {
        $token = request()->csrf();

        $this->assertEquals($token, $_SESSION['_csrf_token']);
    }

    public function testValidateToken()
    {
        $token = request()->csrf();

        $this->assertEquals(false, request()->validateCsrf());

        $_POST['_token'] = $token;

        app()->setInstance(new Request());

        $this->assertEquals(false, request()->validateCsrf());

        $_GET['_token'] = $token;

        app()->setInstance(new Request());

        $this->assertEquals(false, request()->validateCsrf());

        $_POST['_token'] = request()->csrf();

        app()->setInstance(new Request());

        $this->assertEquals(true, request()->validateCsrf());
    }
}
