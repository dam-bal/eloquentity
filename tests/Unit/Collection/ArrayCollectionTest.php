<?php

namespace Eloquentity\Tests\Unit\Collection;

use Eloquentity\Collection\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ArrayCollectionTest extends TestCase
{
    public function testAddWontAddSameItem(): void
    {
        $entity = new TestEntityArrayCollection(1);

        $collection = new ArrayCollection([$entity]);

        $collection->add($entity);

        self::assertCount(1, $collection);
        self::assertEquals(
            [
                $entity
            ],
            iterator_to_array($collection)
        );
    }

    public function testAddWillAddItem(): void
    {
        $entity = new TestEntityArrayCollection(1);

        $collection = new ArrayCollection([$entity]);

        $newEntity = new TestEntityArrayCollection(2);

        $collection->add($newEntity);

        self::assertCount(2, $collection);
        self::assertEquals(
            [
                $entity,
                $newEntity
            ],
            iterator_to_array($collection)
        );
    }

    public function testDeleteUnsetsItem(): void
    {
        $entity = new TestEntityArrayCollection(1);

        $collection = new ArrayCollection([$entity]);

        $collection->delete($entity);

        self::assertCount(0, $collection);
        self::assertEquals(
            [],
            iterator_to_array($collection)
        );
    }

    public function testGetReturnsItem(): void
    {
        $entity = new TestEntityArrayCollection(1);

        $collection = new ArrayCollection([$entity]);

        self::assertNotNull($collection->get(0));
    }

    public function testGetReturnsNullWhenItemDoesNotExist(): void
    {
        $entity = new TestEntityArrayCollection(1);

        $collection = new ArrayCollection([$entity]);

        self::assertNull($collection->get(1));
    }
}

final class TestEntityArrayCollection
{
    public function __construct(private readonly int $id)
    {
    }
}
