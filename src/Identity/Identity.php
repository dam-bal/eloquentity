<?php

namespace Eloquentity\Identity;

use Illuminate\Database\Eloquent\Model;

final class Identity
{
    private bool $deleted = false;

    public function __construct(public readonly Model $model, public readonly object $entity)
    {
    }

    public function setDeleted(bool $deleted = true): void
    {
        $this->deleted = $deleted;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }
}
