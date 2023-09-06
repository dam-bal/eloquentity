<?php

namespace Eloquentity\Factory;

use Eloquentity\Attribute\Id;
use Eloquentity\Exception\EloquentityException;
use Eloquentity\Reflection\ReflectionClass;
use Eloquentity\Reflection\ReflectionProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionException;

class ModelFactory
{
    /**
     * @template T of Model
     * @param object $entity
     * @param class-string<T> $modelClass
     * @return T
     * @throws ReflectionException
     * @throws EloquentityException
     */
    public function create(object $entity, string $modelClass): Model
    {
        $entityClassReflection = ReflectionClass::create($entity::class);

        $modelClassReflection = ReflectionClass::create($modelClass);

        /** @var T $model */
        $model = $modelClassReflection->newInstance();

        $idProperty = $entityClassReflection->getProperty(Str::camel($model->getKeyName()));

        if ($idProperty->isInitialized($entity) && $idValue = $idProperty->getValue($entity)) {
            $model->{$model->getKeyName()} = $idValue;
        }

        $entityProperties = array_filter(
            $entityClassReflection->getProperties(),
            static function (ReflectionProperty $property) use ($entity, $idProperty, $model): bool {
                return $property->isInitialized($entity)
                    && $property->getName() !== $idProperty->getName()
                    && !$model->isRelation($property->getName());
            }
        );

        foreach ($entityProperties as $property) {
            $model->setAttribute(Str::snake($property->getName()), $property->getValue($entity));
        }

        return $model;
    }
}
