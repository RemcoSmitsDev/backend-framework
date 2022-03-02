<?php

declare(strict_types=1);

namespace Framework\Tests\Container;

use Framework\Container\Container;
use Framework\Http\Request;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function test_add_singleton()
    {
        $container = new Container();

        $container->addSingleton(new Request());

        $this->assertInstanceOf(Request::class, $container->getSingleton(Request::class));
    }

    public function test_add_binding()
    {
        $container = new Container();

        $container->bind(TH::class, function () {
            return new Request();
        });

        $this->assertInstanceOf(Request::class, $container->getBinding(TH::class));

        $container = new Container();

        $container->bind(TH::class, new Request());

        $this->assertInstanceOf(Request::class, $container->getBinding(TH::class));
    }
}

class TH implements THInterface
{
    public function __construct()
    {
    }
}

interface THInterface
{
    public function __construct();
}
