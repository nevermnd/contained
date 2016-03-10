<?php

namespace Contained\Test\Stub;

class FooImpl implements FooInterface
{
    public function get(Bar $bar, Qux $qux, $int = 0)
    {
        return [$bar, $qux, $int];
    }
}