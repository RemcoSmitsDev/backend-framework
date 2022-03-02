<?php

declare(strict_types=1);

namespace tests\Http\Route;

use Closure;
use Framework\Http\Request;
use Framework\Http\Route\Route;
use PHPUnit\Framework\TestCase;

class CustomMiddlewareClass
{
    public function handle(array $route, Closure $next)
    {
        // return false when middleware need to fail
        if (true !== false) {
            return false;
        }

        // when middleware is successful
        return $next();
    }
}

class RegisterRouteTest extends TestCase
{
    private ?Request $request = null;

    public function setUp(): void
    {
        $this->request ?: request();

        parent::setUp();
    }

    public function test_register_simple_route()
    {
        $route = new Route();

        $route->get('/get/route', function () {
            echo 'get route';
        });

        $this->assertCount(1, $route->getRoutes());
        $this->assertEquals('/get/route', $route->getRoutes()[0]['uri']);
        $this->assertEquals(['GET', 'HEAD'], $route->getRoutes()[0]['methods']);
        $this->assertEquals([], $route->getRoutes()[0]['middlewares']);
        $this->assertEquals([], $route->getRoutes()[0]['patterns']);
        $this->assertEquals('', $route->getRoutes()[0]['name']);

        $route = new Route();

        $route->post('/post/route', function () {
            echo 'post route';
        });

        $this->assertCount(1, $route->getRoutes());
        $this->assertEquals('/post/route', $route->getRoutes()[0]['uri']);
        $this->assertEquals(['POST'], $route->getRoutes()[0]['methods']);
        $this->assertEquals([], $route->getRoutes()[0]['middlewares']);
        $this->assertEquals([], $route->getRoutes()[0]['patterns']);
        $this->assertEquals('', $route->getRoutes()[0]['name']);
    }

    public function test_register_route_with_name()
    {
        $route = new Route();

        $route->get('/get/route', function () {
            echo 'get route';
        })->name('test_name');

        $this->assertCount(1, $route->getRoutes());
        $this->assertEquals('/get/route', $route->getRoutes()[0]['uri']);
        $this->assertEquals(['GET', 'HEAD'], $route->getRoutes()[0]['methods']);
        $this->assertEquals([], $route->getRoutes()[0]['middlewares']);
        $this->assertEquals([], $route->getRoutes()[0]['patterns']);
        $this->assertEquals('test_name', $route->getRoutes()[0]['name']);
    }

    public function test_register_route_with_middleware()
    {
        $route = new Route();

        $route->middleware(false)->get('/get/route', fn () => false);

        $this->assertCount(1, $route->getRoutes());
        $this->assertEquals('/get/route', $route->getRoutes()[0]['uri']);
        $this->assertEquals(['GET', 'HEAD'], $route->getRoutes()[0]['methods']);
        $this->assertEquals([false], $route->getRoutes()[0]['middlewares']);
        $this->assertEquals([], $route->getRoutes()[0]['patterns']);
        $this->assertEquals('', $route->getRoutes()[0]['name']);
    }
}
