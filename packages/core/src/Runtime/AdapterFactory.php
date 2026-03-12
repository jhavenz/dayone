<?php

declare(strict_types=1);

namespace DayOne\Runtime;

use ReflectionClass;

final class AdapterFactory
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @param \Closure(T): void $initializer
     * @return T
     */
    public function createLazy(string $class, \Closure $initializer): object
    {
        $reflector = new ReflectionClass($class);

        return $reflector->newLazyGhost(initializer: $initializer);
    }
}
