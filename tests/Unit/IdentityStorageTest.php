<?php

namespace Eloquentity\Tests\Unit;

use Eloquentity\Identity\IdentityStorage;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use stdClass;

final class IdentityStorageTest extends MockeryTestCase
{
    private IdentityStorage $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new IdentityStorage();
    }

    public function testAddIdentityAddsIdentity(): void
    {
        $modelMock = Mockery::mock(Model::class);
        $modelMock->shouldIgnoreMissing();
        $modelMock->allows()->getKey()->andReturn(1);

        $entity = new stdClass();

        $this->sut->addIdentity($modelMock, $entity);

        self::assertNotNull($this->sut->getIdentityByObjectId(spl_object_id($entity)));
        self::assertNotNull($this->sut->getIdentity(stdClass::class, 1));
        self::assertNotEmpty($this->sut->getIdentityMapIndex());
        self::assertEquals(1, $this->sut->getIdentityMapCount());
    }

    public function testGetIdentityByObjectIdReturnsNullIfIdentityDoesntExist(): void
    {
        self::assertNull($this->sut->getIdentityByObjectId(123));
    }

    public function testGetIdentityReturnsNullIfIdentityDoesntExist(): void
    {
        self::assertNull($this->sut->getIdentity(stdClass::class, 1));
    }

    public function testAddIdentityIncrementsCount(): void
    {
        $modelMock1 = Mockery::mock(Model::class);
        $modelMock1->shouldIgnoreMissing();
        $modelMock1->allows()->getKey()->andReturn(1);

        $modelMock2 = Mockery::mock(Model::class);
        $modelMock2->shouldIgnoreMissing();
        $modelMock2->allows()->getKey()->andReturn(2);

        $modelMock3 = Mockery::mock(Model::class);
        $modelMock3->shouldIgnoreMissing();
        $modelMock3->allows()->getKey()->andReturn(3);

        $entity1 = new stdClass();
        $entity2 = new stdClass();
        $entity3 = new stdClass();

        $this->sut->addIdentity($modelMock1, $entity1);
        $this->sut->addIdentity($modelMock2, $entity2);
        $this->sut->addIdentity($modelMock3, $entity3);

        self::assertEquals(3, $this->sut->getIdentityMapCount());
    }
}
