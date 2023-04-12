<?php

namespace Eloquentity\Tests\Unit\Reflection;

use Eloquentity\Attribute\Id;
use Eloquentity\Reflection\ReflectionProperty;
use PHPUnit\Framework\TestCase;

final class ReflectionPropertyTest extends TestCase
{
    public function testGetTypeReturnsTypeWhenTypeIsSet(): void
    {
        $propertyToTest = new ReflectionProperty(EntityWithCollection::class, 'collection');

        self::assertEquals(Entity::class, $propertyToTest->getCollectionValueType());
    }

    public function testGetTypeReturnsNullWhenTypeIsNotSet(): void
    {
        $propertyToTest = new ReflectionProperty(EntityWithCollectionWithoutType::class, 'collection');

        self::assertEquals(null, $propertyToTest->getCollectionValueType());
    }

    public function testHasAttributeReturnsTrueIfPropertyHasAttribute(): void
    {
        $propertyToTest = new ReflectionProperty(EntityWithIdAttribute::class, 'id');

        self::assertTrue($propertyToTest->hasAttribute(Id::class));
    }

    public function testHasAttributeReturnsFalseIfPropertyDoesNotHaveAttribute(): void
    {
        $propertyToTest = new ReflectionProperty(EntityWithoutIdAttribute::class, 'id');

        self::assertFalse($propertyToTest->hasAttribute(Id::class));
    }
}

final class Entity
{
}

final class EntityWithoutIdAttribute
{
    public function __construct(
        public readonly int $id
    ) {
    }
}

final class EntityWithIdAttribute
{
    public function __construct(
        #[Id]
        public readonly int $id
    ) {
    }
}

final class EntityWithCollection
{
    /**
     * @param array<Entity> $collection
     */
    public function __construct(
        private readonly array $collection,
    ) {
    }
}

final class EntityWithCollectionWithoutType
{
    public function __construct(
        private readonly array $collection,
    ) {
    }
}
