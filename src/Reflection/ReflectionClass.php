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

    /** @var array<string, ReflectionProperty[]> */
    private static array $propertiesWithAttribute = [];

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

    /**
     * @param class-string $attributeClass
     * @return ReflectionProperty[]
     * @throws ReflectionException
     */
    public function getPropertiesWithAttribute(string $attributeClass): array
    {
        $index = sprintf('%s-%s-%s', static::class, $this->getName(), $attributeClass);

        if (isset(static::$propertiesWithAttribute[$index])) {
            return static::$propertiesWithAttribute[$index];
        }

        $properties = $this->getProperties();

        $propertiesWithAttribute = [];
        foreach ($properties as $property) {
            $attributes = $property->getAttributes($attributeClass);

            if (empty($attributes)) {
                continue;
            }

            $propertiesWithAttribute[] = $property;
        }

        return static::$propertiesWithAttribute[$index] = $propertiesWithAttribute;
    }
}
