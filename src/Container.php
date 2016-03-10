<?php

namespace Contained;

use Contained\Exceptions\UnresolvableDependencyException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

class Container
{
    /**
     * Container bindings
     *
     * @var array
     */
    private $bindings = [];
    /**
     * Container singleton bindings
     *
     * @var array
     */
    private $singletons = [];

    /**
     * Call a callable resolving it's parameters dependencies
     *
     * @param callable $callable
     *
     * @return mixed
     */
    public function call(callable $callable)
    {
        return call_user_func_array($callable, $this->resolveDependencies(
            $this->getReflectionForCallable($callable)
        ));
    }

    /**
     * Binds an implementation on the container
     *
     * @param mixed $abstract
     * @param mixed $concrete
     *
     * @return $this
     */
    public function bind($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;

        return $this;
    }

    /**
     * Binds a singleton implementation on the container
     *
     * @param mixed $abstract
     * @param mixed $concrete
     *
     * @return $this
     */
    public function singleton($abstract, $concrete)
    {
        $this->singletons[$abstract] = $concrete;

        return $this;
    }

    /**
     * Make an object to the given abstract bind
     *
     * @param mixed $abstract
     *
     * @return mixed
     */
    public function make($abstract)
    {
        if (!is_null($concrete = $this->getSingleton($abstract))) {
            return $this->setSingleton($abstract, $this->createObject($concrete));
        } elseif (!is_null($concrete = $this->getBind($abstract))) {
            return $this->createObject($concrete);
        }

        return $this->createObject($abstract);
    }

    /**
     * @param mixed $abstract
     * @param mixed $concrete
     *
     * @return mixed
     */
    protected function setSingleton($abstract, $concrete)
    {
        $this->singleton($abstract, $concrete);

        return $concrete;
    }

    /**
     * Creates an object given a concrete implementation
     *
     * @param mixed $concrete
     *
     * @return mixed
     */
    protected function createObject($concrete)
    {
        if (is_object($concrete)) {
            return $concrete;
        }

        $reflection = new ReflectionClass($concrete);

        $constructorArgs = $this->resolveDependencies($reflection->getConstructor());

        return $reflection->newInstanceArgs($constructorArgs);
    }

    /**
     * @param mixed $abstract
     *
     * @return mixed
     */
    protected function getBind($abstract)
    {
        return isset($this->bindings[$abstract]) ? $this->bindings[$abstract] : null;
    }

    /**
     * @param mixed $abstract
     *
     * @return mixed
     */
    protected function getSingleton($abstract)
    {
        return isset($this->singletons[$abstract]) ? $this->singletons[$abstract] : null;
    }

    /**
     * Resolve the parameters dependencies
     *
     * @param ReflectionFunctionAbstract $reflection
     *
     * @return array
     */
    protected function resolveDependencies($reflection)
    {
        $args = [];

        if ($reflection !== null) {
            foreach ($reflection->getParameters() as $parameter) {
                $args[] = $this->resolveDependency($parameter);
            }
        }

        return $args;
    }

    /**
     * @param ReflectionParameter $parameter
     *
     * @return object
     * @throws \Contained\Exceptions\UnresolvableDependencyException
     */
    protected function resolveDependency(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } elseif (($class = $parameter->getClass()) === null) {
            throw new UnresolvableDependencyException("Unable to resolve parameter {$parameter->getName()}");
        }

        return $this->make($class->getName());
    }

    /**
     * @param callable $callable
     *
     * @return ReflectionFunctionAbstract
     */
    protected function getReflectionForCallable(callable $callable)
    {
        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflection = new ReflectionFunction($callable);
        }

        return $reflection;
    }
}