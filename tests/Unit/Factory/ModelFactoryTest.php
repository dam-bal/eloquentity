<?php

namespace Eloquentity\Tests\Unit\Factory;

use Eloquentity\Factory\ModelFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ModelFactoryTest extends MockeryTestCase
{
    private ModelFactory $sut;

    protected function setUp(): void
    {
        $this->sut = new ModelFactory();
    }

    public function testItDoesNotSetIdAttributeWhenItsNull(): void
    {
        $entity = new Entity(null, 'test');

        $model = $this->sut->create($entity, Model::class);

        self::assertNull($model->getKey());
        self::assertArrayNotHasKey('id', $model->getAttributes());
    }

    public function testItSetsAttributeToPropertyValueWhenItsNotRelation(): void
    {
        $entity = new Entity(1, 'test');

        $model = $this->sut->create($entity, Model::class);

        self::assertEquals(1, $model->getKey());
        self::assertEquals('test', $model->getAttribute('property'));
    }

    public function testItDoesNotSetAttributeToPropertyValueWhenItsRelation(): void
    {
        $entity = new Entity(1, 'test');

        $model = $this->sut->create($entity, ModelWithRelation::class);

        self::assertArrayNotHasKey('property', $model->getAttributes());
    }
}

class Model extends \Illuminate\Database\Eloquent\Model
{
}

class ModelWithRelation extends \Illuminate\Database\Eloquent\Model
{
    public function property(): HasOne
    {
        return $this->hasOne(Model::class);
    }
}

final class Entity
{
    public function __construct(
        public readonly ?int $id,
        public readonly mixed $property
    ) {
    }
}
