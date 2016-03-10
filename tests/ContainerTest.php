<?php

namespace Contained\Test;

use Contained\Container;
use Contained\Test\Stub\Bar;
use Contained\Test\Stub\FooImpl;
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
            $this->assertInstanceOf('Contained\Test\Stub\Qux', $qux);
        };

        $container->call($fn);

        $this->assertTrue($funCalled);
    }

    public function testResolveMethod()
    {
        $container = new Container();

        $this->assertInstanceOf('Contained\Test\Stub\Qux', $container->call(['Contained\Test\Stub\Bar', 'foo']));
    }

    public function testResolveInterface()
    {
        $container = new Container();
        $container->bind('Contained\Test\Stub\FooInterface', 'Contained\Test\Stub\FooImpl');

        $make = $container->make('Contained\Test\Stub\FooInterface');
        $this->assertInstanceOf('Contained\Test\Stub\FooImpl', $make);

        $get = $container->call([$make, 'get']);
        $this->assertInstanceOf('Contained\Test\Stub\Bar', $get[0]);
        $this->assertInstanceOf('Contained\Test\Stub\Qux', $get[1]);
        $this->assertSame(0, $get[2]);
    }

    public function testResolveContainerItself()
    {
        $container = new Container();

        $this->assertInstanceOf('Contained\Container', $container->call([new Bar(), 'container']));
    }

    public function testResolveConstructorArgs()
    {
        $container = new Container();
        $container->bind('Contained\Test\Stub\FooInterface', 'Contained\Test\Stub\FooImpl');

        $make = $container->make('Contained\Test\Stub\Baz');
        $this->assertInstanceOf('Contained\Test\Stub\Baz', $make);
        $this->assertInstanceOf('Contained\Test\Stub\FooImpl', $make->foo);
    }

    public function testMakeSingleton()
    {
        $container = new Container();
        $fooImpl = new FooImpl();
        $container->singleton('Contained\Test\Stub\FooInterface', $fooImpl);

        $this->assertSame($fooImpl, $container->make('Contained\Test\Stub\FooInterface'));
    }

    public function testMakeAliasBind()
    {
        $container = new Container();

        $container->bind('bar', 'Contained\Test\Stub\Bar');
        $this->assertInstanceOf('Contained\Test\Stub\Bar', $container->make('bar'));
    }

    public function testMakeAliasSingleton()
    {
        $container = new Container();
        $container->singleton('bar', 'Contained\Test\Stub\Bar');
        $bar = $container->make('bar');

        $this->assertInstanceOf('Contained\Test\Stub\Bar', $bar);
        $this->assertSame($bar, $container->make('bar'));
    }

    public function testIgnoreInjectionIfDefaultValueIsAvaliable()
    {
        $container = new Container();
        $method = $container->call([new IgnoreStub(), 'foo']);

        $this->assertInstanceOf('Contained\Test\Stub\Qux', $method[0]);
        $this->assertNull($method[1]);
    }

    public function testThrowExceptionIfDefaultValueIsNotAvaliableForPrimaryType()
    {
        $container = new Container();

        $this->setExpectedException('Contained\Exceptions\UnresolvableDependencyException');
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
