<?php

namespace Eloquentity\Identity;

use Illuminate\Database\Eloquent\Model;
use Stringable;

class IdentityStorage
{
    /** @var array<string, Identity> */
    private array $identityMap = [];

    /** @var array<int, Identity> */
    private array $identityMapByObjectId = [];

    /** @var Identity[] */
    private array $identityMapIndex = [];

    private int $identityMapCount = 0;

    public function addIdentity(Model $model, object $entity): void
    {
        $key = sprintf('%s-%s', $entity::class, $model->getKey());

        if (isset($this->identityMap[$key])) {
            return;
        }

        $this->identityMap[$key] = new Identity($model, $entity);
        $this->identityMapByObjectId[spl_object_id($entity)] = $this->identityMap[$key];
        $this->identityMapIndex[] = $this->identityMap[$key];

        $this->identityMapCount++;
    }

    public function getIdentityByObjectId(int $objectId): ?Identity
    {
        return $this->identityMapByObjectId[$objectId] ?? null;
    }

    public function getIdentity(string $entityClass, int|string|Stringable $id): ?Identity
    {
        return $this->identityMap[sprintf('%s-%s', $entityClass, $id)] ?? null;
    }

    public function getIdentityMapCount(): int
    {
        return $this->identityMapCount;
    }

    /**
     * @return Identity[]
     */
    public function getIdentityMapIndex(): array
    {
        return $this->identityMapIndex;
    }
}
