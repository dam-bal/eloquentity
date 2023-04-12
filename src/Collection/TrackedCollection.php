<?php

namespace Eloquentity\Collection;

/**
 * @template T of object
 * @extends ArrayCollection<T>
 */
final class TrackedCollection extends ArrayCollection
{
    /** @var T[] */
    protected array $addedItems = [];

    /** @var T[] */
    protected array $deletedItems = [];

    /**
     * @param T $item
     */
    public function add(object $item): void
    {
        if (in_array($item, $this->addedItems, true)) {
            return;
        }

        $this->addedItems[] = $item;

        parent::add($item);
    }

    /**
     * @param T $item
     */
    public function delete(object $item): void
    {
        $itemIndex = array_search($item, $this->addedItems, true);

        if ($itemIndex !== false) {
            unset($this->addedItems[$itemIndex]);

            $this->addedItems = array_values($this->addedItems);

            return;
        }

        $this->deletedItems[] = $item;

        $this->deletedItems = array_values($this->deletedItems);

        parent::delete($item);
    }

    /**
     * @return T[]
     */
    public function getAddedItems(): array
    {
        return $this->addedItems;
    }

    /**
     * @return T[]
     */
    public function getDeletedItems(): array
    {
        return $this->deletedItems;
    }

    public function clear(): void
    {
        $this->deletedItems = [];
        $this->addedItems = [];
    }
}
