<?php

namespace Contained\Test\Stub;

use Contained\Container;

class Bar
{
    public function foo(Qux $qux)
    {
        return $qux;
    }

    public function container(Container $container)
    {
        return $container;
    }
}