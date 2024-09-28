<?php

namespace Eloquentity;

use Eloquentity\Exception\EloquentityException;
use Eloquentity\Factory\ModelFactory;
use Eloquentity\Identity\IdentityStorage;
use Eloquentity\Processor\RelationProcessor;
use Eloquentity\Reflection\ReflectionClass;
use Eloquentity\Reflection\ReflectionProperty;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionNamedType;
use Stringable;

class Eloquentity
{
    public function __construct(
        private readonly IdentityStorage $identityStorage,
        private readonly ModelFactory $modelFactory,
        private readonly RelationProcessor $relationProcessor
    ) {
    }

    public static function create(): Eloquentity
    {
        $modelFactory = new ModelFactory();
        $identityStorage = new IdentityStorage();

        return new Eloquentity($identityStorage, $modelFactory, new RelationProcessor($modelFactory, $identityStorage));
    }

    /**
     * @template T of object
     * @param class-string<T> $entityClass
     * @param array<class-string, string[]> $withoutRelations
     * @return T
     * @throws ReflectionException
     * @throws EloquentityException
     */
    public function map(Model $model, string $entityClass, array $withoutRelations = [])
    {
        $identity = $this->identityStorage->getIdentity($entityClass, $model->getKey());
        if ($identity) {
            /** @var T $entity */
            $entity = $identity->entity;

            return $entity;
        }

        $entityClassReflection = ReflectionClass::create($entityClass);

        /** @var T $entity */
        $entity = $entityClassReflection->newInstanceWithoutConstructor();

        $this->identityStorage->addIdentity($model, $entity);

        $properties = [];
        foreach ($entityClassReflection->getProperties() as $property) {
            $propertyName = $property->getName();
            $attributeName = Str::snake($propertyName);

            $isRelation = $model->isRelation($propertyName);

            if (!$isRelation) {
                $properties[$propertyName] = $model->getAttribute($attributeName);

                continue;
            }

            if (!in_array($propertyName, $withoutRelations[$model::class] ?? [])) {
                $relation = $model->getAttribute($propertyName);

                $isCollection = $relation instanceof Collection;

                if ($isCollection) {
                    $type = $property->getCollectionValueType();
                    if (!$type) {
                        throw new EloquentityException(
                            sprintf(
                                'No collection type for "%s" property in "%s" class.',
                                $propertyName,
                                $entityClass
                            )
                        );
                    }

                    $entities = [];
                    foreach ($relation as $relationModel) {
                        $entities[] = $this->identityStorage->getIdentity($type, $relationModel->getKey())?->entity
                            ?? $this->map($relationModel, $type, $withoutRelations);
                    }

                    $typeInfo = $property->getTypeInfo();

                    if ($typeInfo && $typeInfo->getBuiltinType() === 'array') {
                        $properties[$propertyName] = $entities;
                    }

                    if ($typeInfo && $typeInfo->getClassName() && $typeInfo->getBuiltinType() === 'object') {
                        /** @var class-string $class */
                        $class = $typeInfo->getClassName();

                        $classRef = new ReflectionClass($class);

                        if (!$classRef->isInstantiable() || $classRef->isInterface()) {
                            throw new EloquentityException($class . ' is not instantiable.');
                        }

                        $properties[$propertyName] = new $class($entities);
                    }

                    continue;
                }

                if ($relation instanceof Model && $property->getType() instanceof ReflectionNamedType) {
                    /** @var class-string $type */
                    $type = $property->getType()->getName();

                    $properties[$propertyName] = $this->identityStorage->getIdentity(
                        $property->getType()->getName(),
                        $relation->getKey()
                    )?->entity ?? $this->map($relation, $type, $withoutRelations);

                    continue;
                }

                $properties[$propertyName] = null;
            }
        }

        foreach ($properties as $property => $value) {
            $entityClassReflection->getProperty($property)->setValue($entity, $value);
        }

        return $entity;
    }

    /**
     * @template T of Model
     * @param class-string<T> $modelClass
     * @throws ReflectionException
     */
    public function persist(object $entity, string $modelClass): int|string|Stringable
    {
        $model = $this->modelFactory->create($entity, $modelClass);

        $model->save();

        $this->identityStorage->addIdentity($model, $entity);

        return $model->getKey();
    }

    /**
     * @throws ReflectionException|EloquentityException
     */
    public function flush(bool $transaction = true): void
    {
        if ($transaction) {
            DB::transaction($this->flushHandler(...));

            return;
        }

        $this->flushHandler();
    }

    /**
     * @throws ReflectionException
     * @throws EloquentityException
     */
    private function flushHandler(): void
    {
        for ($i = 0; $i < $this->identityStorage->getIdentityMapCount(); $i++) {
            $identity = $this->identityStorage->getIdentityMapIndex()[$i];

            if ($identity->isDeleted()) {
                continue;
            }

            $entity = $identity->entity;
            $model = $identity->model;

            $entityClassReflection = ReflectionClass::create($entity::class);

            $entityProperties = array_filter(
                $entityClassReflection->getProperties(),
                static function (ReflectionProperty $property) use ($entity): bool {
                    return $property->isInitialized($entity);
                }
            );

            foreach ($entityProperties as $property) {
                if (
                    $property->getValue($entity) === null &&
                    $property->getName() === Str::camel($model->getKeyName())
                ) {
                    continue;
                }

                $propertyName = $property->getName();
                $attributeName = Str::snake($propertyName);

                $isRelation = $model->isRelation($propertyName);

                $value = $property->getValue($entity);

                if (!$isRelation) {
                    $model->setAttribute($attributeName, $value);
                }

                if ($isRelation) {
                    $this->relationProcessor->process(
                        $model->{$propertyName}(),
                        $value
                    );
                }
            }

            $model->save();
        }
    }
}
