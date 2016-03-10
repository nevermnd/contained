<?php

namespace Contained\Test;

use Contained\Container;
use Contained\Exceptions\UnresolvableDependencyException;
use Contained\Test\Stub\Bar;
use Contained\Test\Stub\Baz;
use Contained\Test\Stub\FooImpl;
use Contained\Test\Stub\FooInterface;
use Contained\Test\Stub\Qux;
use PHPUnit_Framework_TestCase;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    public function testResolveClosure()
    {
        $funCalled = false;
        $container = new Container();

        $fn = function (Qux $qux) use (&$funCalled) {
            $funCalled = true;
            $this->assertInstanceOf(Qux::class, $qux);
        };

        $container->call($fn);

        $this->assertTrue($funCalled);
    }

    public function testResolveMethod()
    {
        $container = new Container();

        $this->assertInstanceOf(Qux::class, $container->call([new Bar(), 'foo']));
    }

    public function testResolveInterface()
    {
        $container = new Container();
        $container->bind(FooInterface::class, FooImpl::class);

        $make = $container->make(FooInterface::class);
        $this->assertInstanceOf(FooImpl::class, $make);

        $get = $container->call([$make, 'get']);
        $this->assertInstanceOf(Bar::class, $get[0]);
        $this->assertInstanceOf(Qux::class, $get[1]);
        $this->assertSame(0, $get[2]);
    }

    public function testResolveSelf()
    {
        $container = new Container();

        $this->assertInstanceOf(Container::class, $container->call([new Bar(), 'container']));
    }

    public function testResolveConstructorArgs()
    {
        $container = new Container();
        $container->bind(FooInterface::class, FooImpl::class);

        $make = $container->make(Baz::class);
        $this->assertInstanceOf(Baz::class, $make);
        $this->assertInstanceOf(FooImpl::class, $make->foo);
    }

    public function testMakeSingleton()
    {
        $container = new Container();
        $fooImpl = new FooImpl();
        $container->singleton(FooInterface::class, $fooImpl);

        $this->assertSame($fooImpl, $container->make(FooInterface::class));
    }

    public function testMakeAliasBind()
    {
        $container = new Container();

        $container->bind('bar', Bar::class);
        $this->assertInstanceOf(Bar::class, $container->make('bar'));
    }

    public function testMakeAliasSingleton()
    {
        $container = new Container();
        $container->singleton('bar', Bar::class);
        $bar = $container->make('bar');

        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertSame($bar, $container->make('bar'));
    }

    public function testIgnoreInjectionIfDefaultValueIsAvaliable()
    {
        $container = new Container();
        $method = $container->call([new IgnoreStub(), 'foo']);

        $this->assertInstanceOf(Qux::class, $method[0]);
        $this->assertNull($method[1]);
    }

    public function testThrowExceptionIfDefaultValueIsNotAvaliableForPrimaryType()
    {
        $container = new Container();

        $this->setExpectedException(UnresolvableDependencyException::class);
        $container->call([new IgnoreStub(), 'qux']);
    }
}

class IgnoreStub
{
    public function foo(Qux $qux, Bar $bar = null)
    {
        return [$qux, $bar];
    }

    public function qux($bool)
    {
        //
    }
}
