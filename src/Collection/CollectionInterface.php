<?php

namespace Eloquentity\Collection;

/**
 * @template T of object
 */
interface CollectionInterface
{
    /**
     * @param T $item
     */
    public function add(object $item): void;

    /**
     * @return T|null
     */
    public function get(int $index): ?object;

    /**
     * @param T $item
     */
    public function delete(object $item): void;
}
