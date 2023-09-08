<?php

namespace Eloquentity\Factory;

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
     */
    public function create(object $entity, string $modelClass): Model
    {
        $entityClassReflection = ReflectionClass::create($entity::class);

        $modelClassReflection = ReflectionClass::create($modelClass);

        /** @var T $model */
        $model = $modelClassReflection->newInstance();

        try {
            $idProperty = $entityClassReflection->getProperty(Str::camel($model->getKeyName()));
        } catch (ReflectionException) {
            $idProperty = null;
        }

        $entityProperties = array_filter(
            $entityClassReflection->getProperties(),
            static function (ReflectionProperty $property) use ($entity, $model): bool {
                return $property->isInitialized($entity) && !$model->isRelation($property->getName());
            }
        );

        foreach ($entityProperties as $property) {
            if (
                $idProperty &&
                $property->getValue($entity) === null &&
                $property->getName() === $idProperty->getName()
            ) {
                continue;
            }

            $model->setAttribute(Str::snake($property->getName()), $property->getValue($entity));
        }

        return $model;
    }
}
