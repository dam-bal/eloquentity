<?php

namespace Eloquentity\Tests\Unit\Reflection;

use Eloquentity\Attribute\Id;
use Eloquentity\Reflection\ReflectionClass;
use PHPUnit\Framework\TestCase;

final class ReflectionClassTest extends TestCase
{
    public function testCreateCachesReflectionClass(): void
    {
        $classReflection1 = ReflectionClass::create(ClassWithProperty::class);
        $classReflection2 = ReflectionClass::create(ClassWithProperty::class);

        self::assertEquals(spl_object_id($classReflection1), spl_object_id($classReflection2));
    }

    public function testGetPropertiesCachesProperties(): void
    {
        $classReflection = ReflectionClass::create(ClassWithProperty::class);

        $properties1 = $classReflection->getProperties();
        $properties2 = $classReflection->getProperties();

        self::assertEquals(spl_object_id($properties1[0]), spl_object_id($properties2[0]));
    }

    public function testGetPropertiesWithAttributeCachesProperties(): void
    {
        $classReflection = ReflectionClass::create(ClassWithIdAttribute::class);

        $propertiesWithIdAttribute1 = $classReflection->getPropertiesWithAttribute(Id::class);
        $propertiesWithIdAttribute2 = $classReflection->getPropertiesWithAttribute(Id::class);

        self::assertEquals(
            spl_object_id($propertiesWithIdAttribute1[0]),
            spl_object_id($propertiesWithIdAttribute2[0])
        );
    }

    public function testGetPropertiesWithAttributeReturnsPropertiesWithAttribute(): void
    {
        $classReflection = ReflectionClass::create(ClassWithIdAttribute::class);

        $propertiesWithIdAttribute = $classReflection->getPropertiesWithAttribute(Id::class);

        self::assertNotEmpty($propertiesWithIdAttribute);
        self::assertEquals('id', $propertiesWithIdAttribute[0]->getName());
    }

    public function testGetPropertiesWithAttributeReturnsEmptyArrayWhenClassDoesNotHavePropertiesWithAttribute(): void
    {
        $classReflection = ReflectionClass::create(ClassWithProperty::class);

        $propertiesWithIdAttribute = $classReflection->getPropertiesWithAttribute(Id::class);

        self::assertEmpty($propertiesWithIdAttribute);
    }
}

final class ClassWithProperty
{
    public function __construct(private readonly int $id)
    {
    }
}

final class ClassWithIdAttribute
{
    public function __construct(
        #[Id]
        private readonly int $id
    ) {
    }
}
