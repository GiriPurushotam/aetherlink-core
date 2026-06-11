<?php

declare(strict_types=1);

namespace AetherLink\Core\Container;

use Exception;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Enterprise-Grade Dependency Injection Container.
 * Architected for strict type-safety, auto-wiring via reflection, and singleton management.
 */

final class Container
{
    /**
     *
     * @var array<string, mixed> Holds resolved singleton instance.
     */
    private array $instances = [];

    /**
     *
     * @var array<string, callable> holds explicit dependency definitions/factoris
     */
    private array $definitions = [];

    /**
     * Bind an expicit factory or implementation resolver to the container
     *
     * @param string $id
     * @param callable $resolver
     * @return void
     */
    public function bind(string $id, callable $resolver): void
    {
        $this->definitions[$id] = $resolver;
    }

    /**
     * Bind a singleton instance that should only be instantiated once across the runtime lifecycle. 
     *
     * @param string $id
     * @param callable $resolver
     * @return void
     */
    public function singleton(string $id, callable $resolver): void
    {
        $this->definitions[$id] = function (self $container) use ($resolver, $id) {
            if (!isset($this->instances[$id])) {
                $this->instances[$id] = $resolver($container);
            }

            return $this->instances[$id];
        };
    }

    /**
     * Resolves and extracts an instantiated class out of the container matrix.
     * * @template T
     * @param class-string<T>|string $id
     * @return T
     */
    public function make(string $id): mixed
    {
        // 1. If an explicit definitions exists, evaluate its resolver factory immediately 
        if (isset($this->definitions[$id])) {
            return $this->definitions[$id]($this);
        }

        // If no definitions exists, attemp Auto-wiring via the PHP Reflection Engine
        return $this->resolveAutoWiredClass($id);
    }



    public function resolveAutoWiredClass(string $id): mixed
    {
        if (!class_exists($id)) {
            throw new RuntimeException(sprintf('Target resolution target identity [%s] does not exists within the runtime system map.', $id));
        }

        try {
            $reflectionClass = new ReflectionClass($id);

            if (!$reflectionClass->isInstantiable()) {
                throw new RuntimeException(sprintf('Target class [%s] cannot be initialized (Abstract class or Interface missing as explicit binding).', $id));
            }

            $constructor = $reflectionClass->getConstructor();

            // If no constructor exists, this class has zero dependencies. Instantiate it immediatly.
            if ($constructor === null) {
                return new $id();
            }

            $parameters = $constructor->getParameters();
            $dependencies = [];

            //Core Auto-Wiring loop: Analyze parameter type-hints recursively.
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                        continue;
                    }

                    throw new RuntimeException(sprintf('Cannot Auto-Wire parameter $%s in class %s: Missing a valid class type-hint or default fallback value.', $parameter->getName(), $id));
                }

                //Recursively resolve the class dependency through the container.
                $dependencies[] = $this->make($type->getName());
            }

            return $reflectionClass->newInstanceArgs($dependencies);
        } catch (Exception $e) {
            throw new RuntimeException(sprintf('Compilation Error during Auto-Wiring lifecycle of target [%s]: %s', $id, $e->getMessage()), 0, $e);
        }
    }
}
