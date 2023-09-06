<?php

namespace Eloquentity\Reflection;

use ReflectionException;

/**
 * @template T of object
 * @extends \ReflectionClass<T>
 */
class ReflectionClass extends \ReflectionClass
{
    /** @var array<string, ReflectionClass<T>> */
    private static array $classes = [];

    /** @var array<string, ReflectionProperty[]> */
    private static array $properties = [];

    /**
     * @param class-string<T> $class
     * @throws ReflectionException
     */
    public static function create(string $class): static
    {
        $index = sprintf('%s-%s', static::class, $class);

        if (isset(static::$classes[$index])) {
            return self::$classes[$index];
        }

        return static::$classes[$index] = new static($class);
    }

    /**
     * @return ReflectionProperty[]
     * @throws ReflectionException
     */
    public function getProperties(?int $filter = null): array
    {
        $index = sprintf('%s-%s-%s', static::class, $this->getName(), $filter);

        if (isset(static::$properties[$index])) {
            return static::$properties[$index];
        }

        $parentProperties = parent::getProperties($filter);

        $properties = [];
        foreach ($parentProperties as $property) {
            $properties[] = new ReflectionProperty(
                $this->getName(),
                $property->getName()
            );
        }

        return static::$properties[$index] = $properties;
    }
}
