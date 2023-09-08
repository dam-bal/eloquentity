<?php

namespace Eloquentity\Processor;

use Eloquentity\Factory\ModelFactory;
use Eloquentity\Identity\Identity;
use Eloquentity\Identity\IdentityStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionException;

class RelationProcessor
{
    public function __construct(
        private readonly ModelFactory $modelFactory,
        private readonly IdentityStorage $identityStorage
    ) {
    }

    /**
     * @param object|iterable<mixed>|null $value
     * @throws ReflectionException
     */
    public function process(Relation $relation, object|iterable|null $value): void
    {
        if (
            is_iterable($value) &&
            ($relation instanceof HasMany || $relation instanceof BelongsToMany || $relation instanceof MorphMany)
        ) {
            $this->processRelationThatReturnsCollectionOfModels($value, $relation);
        }

        if (
            (is_object($value) || $value === null) &&
            ($relation instanceof HasOne || $relation instanceof BelongsTo || $relation instanceof MorphOne)
        ) {
            $this->processRelationThatReturnsModel($value, $relation);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function processRelationThatReturnsModel(
        ?object $value,
        HasOne|BelongsTo|MorphOne $relation
    ): void {
        $isRelationInstanceOfBelongsTo = $relation instanceof BelongsTo;

        if (!$value && $isRelationInstanceOfBelongsTo) {
            $relation->dissociate();
        }

        if ($value) {
            /** @var Identity $identity */
            $identity = $this->getIdentityOrPersist($value, $relation->getRelated()::class);

            if ($isRelationInstanceOfBelongsTo) {
                $relation->associate($identity->model);
            }

            if (!$isRelationInstanceOfBelongsTo) {
                $relation->save($identity->model);
            }
        }
    }

    /**
     * @template T
     * @param iterable<T> $collection
     * @throws ReflectionException
     */
    private function processRelationThatReturnsCollectionOfModels(
        iterable $collection,
        HasMany|BelongsToMany|MorphMany $relation
    ): void {
        foreach ($collection as $entity) {
            $this->addToRelation($entity, $relation);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function addToRelation(
        object $entity,
        HasOne|MorphOne|BelongsToMany|HasMany|MorphMany|MorphToMany $relation
    ): void {
        /** @var Identity $identity */
        $identity = $this->getIdentityOrPersist($entity, $relation->getRelated()::class);

        $relation->save($identity->model);
    }

    /**
     * @template T of Model
     * @param class-string<T> $modelClass
     * @throws ReflectionException
     */
    private function getIdentityOrPersist(object $entity, string $modelClass): ?Identity
    {
        $entityObjectId = spl_object_id($entity);

        if ($identityForObjectId = $this->identityStorage->getIdentityByObjectId($entityObjectId)) {
            return $identityForObjectId;
        }

        $this->persist($entity, $modelClass);

        return $this->identityStorage->getIdentityByObjectId($entityObjectId);
    }

    /**
     * @template T of Model
     * @param class-string<T> $modelClass
     * @throws ReflectionException
     */
    private function persist(object $entity, string $modelClass): void
    {
        $model = $this->modelFactory->create($entity, $modelClass);

        $model->save();

        $this->identityStorage->addIdentity($model, $entity);
    }
}
