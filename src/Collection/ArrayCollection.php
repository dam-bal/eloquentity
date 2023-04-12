<?php

namespace Eloquentity\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template T of object
 * @implements CollectionInterface<T>
 * @implements IteratorAggregate<int, T>
 */
class ArrayCollection implements CollectionInterface, Countable, IteratorAggregate
{
    /**
     * @param T[] $items
     */
    public function __construct(protected array $items = [])
    {
    }

    /**
     * @param T $item
     */
    public function add(object $item): void
    {
        if (in_array($item, $this->items, true)) {
            return;
        }

        $this->items[] = $item;
    }

    /**
     * @return  T|null
     */
    public function get(int $index): ?object
    {
        return $this->items[$index] ?? null;
    }

    /**
     * @param T $item
     */
    public function delete(object $item): void
    {
        $itemIndex = array_search($item, $this->items, true);

        if ($itemIndex === false) {
            return;
        }

        unset($this->items[$itemIndex]);

        $this->items = array_values($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
