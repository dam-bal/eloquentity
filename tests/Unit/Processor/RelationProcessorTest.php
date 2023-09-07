<?php

namespace Eloquentity\Tests\Unit\Processor;

use Eloquentity\Collection\ArrayCollection;
use Eloquentity\Collection\TrackedCollection;
use Eloquentity\Factory\ModelFactory;
use Eloquentity\Identity\Identity;
use Eloquentity\Identity\IdentityStorage;
use Eloquentity\Processor\RelationProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class RelationProcessorTest extends MockeryTestCase
{
    private ModelFactory|MockInterface $modelFactoryMock;
    private ModelFactory|MockInterface $identityStorageMock;

    private RelationProcessor $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelFactoryMock = Mockery::mock(ModelFactory::class);
        $this->modelFactoryMock->shouldIgnoreMissing();

        $this->identityStorageMock = Mockery::mock(IdentityStorage::class);
        $this->identityStorageMock->shouldIgnoreMissing();

        $this->sut = new RelationProcessor(
            $this->modelFactoryMock,
            $this->identityStorageMock
        );
    }

    public function testItPersistsEntityIfItsNotInIdentityStorage(): void
    {
        $entity = new Entity();

        $value = new ArrayCollection([$entity]);

        $modelMock = $this->getModelMock(1);

        $this->setupIdentityStorage(
            [
                spl_object_id($entity) => [null, new Identity($modelMock, $entity)],
            ]
        );

        $this->modelFactoryMock
            ->expects()
            ->create($entity, $modelMock::class)
            ->andReturn($modelMock);

        $modelMock
            ->expects()
            ->save();

        $this->identityStorageMock
            ->expects()
            ->addIdentity($modelMock, $entity);

        $this->sut->process($this->getRelationMock(HasMany::class, $modelMock), $value);
    }

    public function testItDoesNotPersistEntityIfItsInIdentityStorage(): void
    {
        $entity = new Entity();

        $value = new ArrayCollection([$entity]);

        $this->setupIdentityStorage(
            [
                spl_object_id($entity) => new Identity($this->getModelMock(1), $entity)
            ]
        );

        $this->modelFactoryMock
            ->expects()
            ->create(Mockery::any(), Mockery::any())
            ->never();

        $this->sut->process($this->getRelationMock(HasMany::class, Mockery::mock(Model::class)), $value);
    }

    public function testItSavesModelToRelationThatIsCollectionForArrayCollection(): void
    {
        $entity = new Entity();

        $value = new ArrayCollection([$entity]);

        $modelMock = $this->getModelMock(1);

        $this->setupIdentityStorage(
            [
                spl_object_id($entity) => new Identity($modelMock, $entity)
            ]
        );

        $relationMock = $this->getRelationMock(HasMany::class, $modelMock);

        $relationMock
            ->expects()
            ->save($modelMock);

        $this->sut->process($relationMock, $value);
    }

    public function testItSavesModelToRelationThatIsCollectionForTrackedCollection(): void
    {
        $entity = new Entity();

        $value = new TrackedCollection();

        $value->add($entity);

        $modelMock = $this->getModelMock(1);

        $this->setupIdentityStorage(
            [
                spl_object_id($entity) => new Identity($modelMock, $entity)
            ]
        );

        $relationMock = $this->getRelationMock(HasMany::class, $modelMock);

        $relationMock
            ->expects()
            ->save($modelMock);

        $this->sut->process($relationMock, $value);
    }

    public function testItSavesModelToRelationThatIsModel(): void
    {
        $entity = new Entity();

        $modelMock = $this->getModelMock(1);

        $this->setupIdentityStorage(
            [
                spl_object_id($entity) => new Identity($modelMock, $entity)
            ]
        );

        $relationMock = $this->getRelationMock(HasOne::class, $modelMock);

        $relationMock
            ->expects()
            ->save($modelMock);

        $this->sut->process($relationMock, $entity);
    }

    public function testItAssociatesModelToRelationWhenItsModel(): void
    {
        $entity = new Entity();

        $modelMock = $this->getModelMock(1);

        $this->setupIdentityStorage(
            [
                spl_object_id($entity) => new Identity($modelMock, $entity)
            ]
        );

        $relationMock = $this->getRelationMock(BelongsTo::class, $modelMock);

        $relationMock
            ->expects()
            ->associate($modelMock);

        $this->sut->process($relationMock, $entity);
    }

    public function testItDissociatesModelToRelationWhenItsNull(): void
    {
        $relationMock = $this->getRelationMock(BelongsTo::class, $this->getModelMock(1));

        $relationMock
            ->expects()
            ->dissociate();

        $this->sut->process($relationMock, null);
    }

    /**
     * @template T
     * @param class-string<T> $relationClass
     * @param $relatedModel
     * @return T|MockInterface
     */
    private function getRelationMock(string $relationClass, $relatedModel): MockInterface
    {
        /** @var T $relationMock */
        $relationMock = Mockery::mock($relationClass);
        $relationMock->shouldIgnoreMissing();

        $relationMock->allows()->getRelated()->andReturn($relatedModel);

        return $relationMock;
    }

    private function getModelMock(int $id): Model|MockInterface
    {
        $modelMock = Mockery::mock(Model::class);

        $modelMock->allows()->getKey()->andReturn($id);
        $modelMock->allows()->getKeyName()->andReturn('id');

        return $modelMock;
    }

    private function setupIdentityStorage(array $identityStorage): void
    {
        foreach ($identityStorage as $objectId => $identity) {
            $this->identityStorageMock
                ->allows()
                ->getIdentityByObjectId($objectId)
                ->andReturn(...(!is_array($identity) ? [$identity] : $identity));
        }
    }
}

final class Entity
{
}
