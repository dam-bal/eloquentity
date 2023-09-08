<?php

namespace Eloquentity\Tests\Unit\Reflection;

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
}

final class ClassWithProperty
{
    public function __construct(private readonly int $id)
    {
    }
}
