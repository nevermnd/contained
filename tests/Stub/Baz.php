<?php

namespace Contained\Test\Stub;

class Baz
{
    public $foo;

    public function __construct(FooInterface $fooInterface)
    {
        $this->foo = $fooInterface;
    }
}