<?php

namespace Eloquentity\Tests\Unit\Collection;

use Eloquentity\Collection\TrackedCollection;
use PHPUnit\Framework\TestCase;

final class TrackedCollectionTest extends TestCase
{
    public function testAddAddsItemToAddedItems(): void
    {
        $entity = new TestEntityTrackedCollection(1);

        $collection = new TrackedCollection();

        $collection->add($entity);

        self::assertCount(1, $collection->getAddedItems());
        self::assertEquals(
            [$entity],
            $collection->getAddedItems()
        );
    }

    public function testAddWontAddSameItemToAddedItems(): void
    {
        $entity = new TestEntityTrackedCollection(1);

        $collection = new TrackedCollection();

        $collection->add($entity);
        $collection->add($entity);

        self::assertCount(1, $collection->getAddedItems());
        self::assertEquals(
            [$entity],
            $collection->getAddedItems()
        );
    }

    public function testDeleteWillDeleteItemFromAddedItems(): void
    {
        $entity = new TestEntityTrackedCollection(1);

        $collection = new TrackedCollection();

        $collection->add($entity);
        $collection->delete($entity);

        self::assertCount(0, $collection->getDeletedItems());
        self::assertCount(0, $collection->getAddedItems());
        self::assertEquals(
            [],
            $collection->getAddedItems()
        );
    }

    public function testDeleteWillAddItemToDeletedItems(): void
    {
        $entity = new TestEntityTrackedCollection(1);

        $collection = new TrackedCollection([$entity]);

        $collection->delete($entity);

        self::assertCount(1, $collection->getDeletedItems());
        self::assertEquals(
            [$entity],
            $collection->getDeletedItems()
        );
    }
}

final class TestEntityTrackedCollection
{
    public function __construct(public readonly int $id)
    {
    }
}
