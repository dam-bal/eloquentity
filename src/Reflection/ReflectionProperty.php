<?php

namespace Eloquentity\Reflection;

use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Type;

final class ReflectionProperty extends \ReflectionProperty
{
    private static ?PhpStanExtractor $phpStanExtractor = null;

    /** @var array<string, class-string|null> */
    private static array $types = [];

    /** @var array<string, Type|null> */
    private static array $typeInfo = [];

    public function getTypeInfo(): ?Type
    {
        $index = sprintf('%s-%s', $this->getDeclaringClass()->getName(), $this->getName());

        if (isset(static::$typeInfo[$index])) {
            return static::$typeInfo[$index];
        }

        if (!self::$phpStanExtractor) {
            self::$phpStanExtractor = new PhpStanExtractor();
        }

        return static::$typeInfo[$index] = self::$phpStanExtractor->getTypes(
            $this->getDeclaringClass()->getName(),
            $this->getName()
        )[0] ?? null;
    }

    /**
     * @return class-string|null
     */
    public function getCollectionValueType(): ?string
    {
        $index = sprintf('%s-%s', $this->getDeclaringClass()->getName(), $this->getName());

        if (isset(static::$types[$index])) {
            return static::$types[$index];
        }

        $typeName = $this->getCollectionValueTypeFromClassProperty(
            $this->getDeclaringClass()->getName(),
            $this->getName()
        );

        return static::$types[$index] = $typeName;
    }

    /**
     * @param class-string $class
     * @return class-string|null
     */
    private function getCollectionValueTypeFromClassProperty(string $class, string $property): ?string
    {
        if (!self::$phpStanExtractor) {
            self::$phpStanExtractor = new PhpStanExtractor();
        }

        $types = self::$phpStanExtractor->getTypes($class, $property);

        $type = null;

        if (!empty($types) && !empty($types[0]->getCollectionValueTypes())) {
            $type = $types[0]->getCollectionValueTypes()[0];
        }

        /** @phpstan-ignore-next-line */
        return $type?->getClassName();
    }
}
