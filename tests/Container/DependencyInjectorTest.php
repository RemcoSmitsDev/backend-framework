<?php

declare(strict_types=1);

namespace tests\Container;

use Framework\Container\Container;
use Framework\Container\DependencyInjector;
use Framework\Container\Exceptions\InvalidClassMethodException;
use Framework\Container\Exceptions\MissingArgumentException;
use PHPUnit\Framework\TestCase;

class DependencyInjectorTest extends TestCase
{
    public function test_get_closure_params()
    {
        $closure = function (string $t, User $user, ?string $h, Test $interface, string ...$args) {
            return func_get_args();
        };

        $args = DependencyInjector::resolve(target: $closure)->with(arguments: [
            't'         => 'b',
            'args'      => ['string'],
            'interface' => new User(),
        ])->getParameters();

        $this->assertCount(5, $args);
        $this->assertEquals('b', $args['t']);
        $this->assertInstanceOf(User::class, $args['user']);
        $this->assertEquals(null, $args['h']);
        $this->assertInstanceOf(Test::class, $args['interface']);
        $this->assertEquals(['string'], $args['args']);
    }

    public function test_closure_throw_missing_argument_exception()
    {
        $this->expectException(MissingArgumentException::class);

        $closure = function (string $t, User $user, ?string $h, Test $interface, string ...$args) {
            return func_get_args();
        };

        DependencyInjector::resolve(target: $closure)->getParameters();
    }

    public function test_closure_get_correct_return_value()
    {
        $closure = function (string $t, User $user, ?string $h, Test $interface, string ...$args) {
            return func_get_args();
        };

        $content = DependencyInjector::resolve(target: $closure)->with(arguments: [
            't'         => 'b',
            'interface' => new User(),
            'args'      => 'string',
        ])->getContent();

        $this->assertEquals(['b', new User(), null, new User()], $content);
    }

    public function test_class_method_get_parameters()
    {
        $args = DependencyInjector::resolve(User::class, 'testArguments')->with(arguments: [
            't'         => 'b',
            'args'      => ['string'],
            'interface' => new User(),
        ])->getParameters();

        $this->assertCount(5, $args);
        $this->assertEquals('b', $args['t']);
        $this->assertInstanceOf(User::class, $args['user']);
        $this->assertEquals(null, $args['h']);
        $this->assertInstanceOf(Test::class, $args['interface']);
        $this->assertEquals(['string'], $args['args']);
    }

    public function test_private_class_method_exception()
    {
        $this->expectException(InvalidClassMethodException::class);

        DependencyInjector::resolve(User::class, 'testPrivateMethodException')->getContent();
    }

    public function test_class_method_get_correct_return_value()
    {
        $content = DependencyInjector::resolve(User::class, 'testArguments')->with(arguments: [
            't'         => 'b',
            'args'      => 'string',
            'interface' => new User(),
        ])->getContent();

        $this->assertEquals(['b', new User(), null, new User()], $content);
    }

    public function test_class_with_constructor_argument_value()
    {
        $content = DependencyInjector::resolve(UserWithConstructor::class, 'testConstructorArgumenValue')->with(arguments: ['user' => new User()])->getContent();

        $this->assertInstanceOf(User::class, $content);
        $this->assertEquals(['test'], $content->array);

        Container::getInstance()->bind(Test::class, function () {
            return new User();
        });

        $injector = DependencyInjector::resolve(UserWithConstructor::class, 'testConstructorArgumenValue');

        $content = $injector->getContent();

        $this->assertInstanceOf(User::class, $injector->getClassInstance()->getUser());
        $this->assertInstanceOf(Test::class, $injector->getClassInstance()->getUser());

        $this->assertInstanceOf(User::class, $content);
        $this->assertEquals(['test'], $content->array);

        $content = DependencyInjector::resolve(UserWithConstructor::class, 'testConstructorArgumenValue')->getContent();

        $this->assertInstanceOf(User::class, $content);
        $this->assertEquals(['test'], $content->array);
    }

    public function test_class_with_only_constructor()
    {
        $content = DependencyInjector::resolve(UserWithConstructor::class)->getContent();

        $this->assertInstanceOf(UserWithConstructor::class, $content);
    }

    public function test_class_with_existing_instance()
    {
        $u = new User();

        $u->name = 'test';

        $t = new UserWithConstructor($u);

        $content = DependencyInjector::resolve($t, 'testConstructorArgumenValue')->getContent();

        $this->assertInstanceOf(User::class, $content);
        $this->assertEquals('test', $content->name);
    }
}

class User implements Test
{
    public array $array = ['test'];

    private function testPrivateMethodException()
    {
    }

    public function testArguments(string $t, User $user, ?string $h, Test $interface, string ...$args)
    {
        return func_get_args();
    }
}

class UserWithConstructor
{
    public function __construct(
        private Test $user,
        private ?string $null = ''
    ) {
    }

    public function getUser()
    {
        return $this->user;
    }

    public function testConstructorArgumenValue(): User
    {
        return $this->user;
    }
}

interface Test
{
}
