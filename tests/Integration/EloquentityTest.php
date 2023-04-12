<?php

namespace Eloquentity\Tests\Integration;

use DateTime;
use Eloquentity\Attribute\Id;
use Eloquentity\Collection\ArrayCollection;
use Eloquentity\Collection\CollectionInterface;
use Eloquentity\Collection\TrackedCollection;
use Eloquentity\Eloquentity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Sushi\Sushi;

class EloquentityTest extends TestCase
{
    private const DEFAULT_MODEL1_ATTRIBUTES = [
        'id' => 1,
        'property' => null,
        'value_object_value' => null,
        'date' => null,
        'test_enum' => null,
    ];

    private const DEFAULT_MODEL2_ATTRIBUTES = [
        'id' => 1,
        'property' => null,
    ];

    private Eloquentity $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = Eloquentity::create();
    }

    public function testMapSetsAttribute(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test value'
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        self::assertEquals('test value', $entity->getProperty());
    }

    public function testMapSetsDateCast(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'date' => '2023-05-01'
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        self::assertInstanceOf(DateTime::class, $entity->getDate());
        self::assertEquals('2023-05-01', $entity->getDate()->format('Y-m-d'));
    }

    public function testMapSetsEnumCast(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'test_enum' => 'value_2',
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        self::assertInstanceOf(Enum::class, $entity->getTestEnum());
        self::assertEquals(Enum::VALUE_2, $entity->getTestEnum());
    }

    public function testMapSetsAttributeMutator(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'value_object_value' => 'value object value',
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        self::assertInstanceOf(ValueObject::class, $entity->getValueObject());
        self::assertEquals(new ValueObject('value object value'), $entity->getValueObject());
    }

    public function testMapMSetsNull(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => null,
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        self::assertNull($entity->getProperty());
    }

    public function testMapSetsHasOneRelationship(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test value'
                ]
            )
        ];

        TestModel2::$rows = [
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            )
        ];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        self::assertInstanceOf(Entity2::class, $entity->getEntity());

        self::assertEquals(1, $entity->getEntity()->getId());
        self::assertEquals('test 1', $entity->getEntity()->getProperty());

        self::assertEquals('test value', $entity->getEntity()->getParentEntity()->getProperty());
    }

    public function testMapSetsHasManyRelationship(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test value'
                ]
            )
        ];

        TestModel2::$rows = [
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 2,
                    'property' => 'test 2',
                    'test_model1_id' => 1,
                ]
            )
        ];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        self::assertInstanceOf(TrackedCollection::class, $entity->getEntities());
        self::assertCount(2, $entity->getEntities());

        self::assertEquals('test 1', $entity->getEntities()->get(0)->getProperty());
        self::assertEquals('test 2', $entity->getEntities()->get(1)->getProperty());
    }

    public function testMapSetsBelongsToRelationship(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test value'
                ]
            )
        ];

        TestModel2::$rows = [
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 2,
                    'property' => 'test 2',
                    'test_model1_id' => 1,
                ]
            )
        ];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel2::find(1), Entity2::class);

        self::assertInstanceOf(Entity1::class, $entity->getParentEntity());
        self::assertEquals(1, $entity->getParentEntity()->getId());
        self::assertEquals('test value', $entity->getParentEntity()->getProperty());
    }

    public function testPersistPersistsEntityWithAutoIncrementId(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test 1'
                ]
            )
        ];

        TestModel2::$rows = [];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = new Entity1(null, 'test 2');

        $id = $this->sut->persist($entity, TestModel1::class);

        self::assertEquals(2, $id);

        self::assertNotNull(TestModel1::find($id));
        self::assertEquals('test 2', TestModel1::find($id)->property);
    }

    public function testPersistPersistsEntityWithCustomId(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test 1'
                ]
            )
        ];

        TestModel2::$rows = [];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = new Entity1(123, 'test 2');

        $id = $this->sut->persist($entity, TestModel1::class);

        self::assertEquals(123, $id);

        self::assertNotNull(TestModel1::find($id));
        self::assertEquals('test 2', TestModel1::find($id)->property);
    }

    public function testFlushUpdatesAttribute(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->setProperty('updated');

        $this->sut->flush(false);

        $model = TestModel1::find(1);

        self::assertEquals('updated', $model->property);
    }

    public function testFlushUpdatesAttributeMutator(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->setValueObject(new ValueObject('my new value object'));

        $this->sut->flush(false);

        $model = TestModel1::find(1);

        self::assertEquals(new ValueObject('my new value object'), $model->valueObject);
    }

    public function testFlushUpdatesAttributeCastEnum(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->setTestEnum(Enum::VALUE_1);

        $this->sut->flush(false);

        $model = TestModel1::find(1);

        self::assertEquals(Enum::VALUE_1, $model->test_enum);
    }

    public function testFlushUpdatesAttributeCastDate(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel1::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->setDate(DateTime::createFromFormat('Y-m-d', '2023-05-01'));

        $this->sut->flush(false);

        $model = TestModel1::find(1);

        self::assertInstanceOf(DateTime::class, $model->date);
        self::assertEquals('2023-05-01', $model->date->format('Y-m-d'));
    }

    public function testFlushPersistsEntitiesAddedToCollection(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel2::$rows = [];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->getEntities()->add(new Entity2(null, 'test 1'));

        $this->sut->flush(false);

        $model = TestModel1::find(1)->entities[0];

        self::assertCount(1, TestModel1::find(1)->entities);

        self::assertEquals('test 1', $model->property);
    }

    public function testFlushDeletesDeletedEntitiesFromCollection(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel2::$rows = [
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 2,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
        ];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->getEntities()->delete($entity->getEntities()->get(0));

        $this->sut->flush(false);

        self::assertCount(1, TestModel1::find(1)->entities);
        self::assertEquals(2, TestModel1::find(1)->entities[0]->id);
    }

    public function testFlushPersistsAndAssociatesEntity(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel2::$rows = [];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->setEntity(new Entity2(null, 'test'));

        $this->sut->flush(false);

        self::assertNotNull(TestModel1::find(1)->entity);
        self::assertEquals(1, TestModel1::find(1)->entity->id);
        self::assertEquals('test', TestModel1::find(1)->entity->property);
    }

    public function testFlushUpdatesAttributesOnRelation(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel2::$rows = [
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 2,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
        ];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel1::find(1), Entity1::class);

        $entity->getEntities()->get(1)->setProperty('test 123');

        $this->sut->flush(false);

        self::assertEquals('test 123', TestModel2::find(2)->property);
    }

    public function testFlushUpdatesAttributesOnBelongsToRelation(): void
    {
        TestModel1::$rows = [
            array_merge(
                self::DEFAULT_MODEL1_ATTRIBUTES,
                [
                    'property' => 'test',
                ]
            )
        ];

        TestModel2::$rows = [
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 1,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
            array_merge(
                self::DEFAULT_MODEL2_ATTRIBUTES,
                [
                    'id' => 2,
                    'property' => 'test 1',
                    'test_model1_id' => 1,
                ]
            ),
        ];

        TestModel1::bootSushi();
        TestModel2::bootSushi();

        $entity = $this->sut->map(TestModel2::find(2), Entity2::class);

        $entity->getParentEntity()->setProperty('updated test');

        $this->sut->flush(false);

        self::assertEquals('updated test', TestModel1::find(1)->property);
    }

    public function testFlushPersistsEntitiesAddedToCollectionForPersistedEntity(): void
    {
        TestModel1::$rows = [];
        TestModel2::$rows = [];

        TestModel1::bootSushi();
        TestModel2::bootSushi();


        $entity = new Entity1(null, 'test');

        $entity->getEntities()->add(new Entity2(null, 'test 1'));
        $entity->getEntities()->add(new Entity2(null, 'test 2'));

        $id = $this->sut->persist($entity, TestModel1::class);

        $this->sut->flush(false);

        self::assertEquals(1, $id);
        self::assertEquals('test', TestModel1::find($id)->property);

        self::assertCount(2, TestModel1::find($id)->entities);

        self::assertEquals('test 1', TestModel1::find($id)->entities[0]->property);
        self::assertEquals('test 2', TestModel1::find($id)->entities[1]->property);

        self::assertNotNull(TestModel2::find(1));
        self::assertNotNull(TestModel2::find(2));
    }

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../LaravelApp/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}

class TestModel1 extends Model
{
    use Sushi;

    protected array $schema = [
        'id' => 'integer',
        'property' => 'string',
        'value_object_value' => 'string',
        'date' => 'datetime',
        'test_enum' => 'string',
    ];

    public static array $rows = [];

    public function getRows(): array
    {
        return static::$rows;
    }

    protected $casts = [
        'date' => 'datetime',
        'test_enum' => Enum::class,
    ];

    protected function valueObject(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => new ValueObject(
                $attributes['value_object_value'] ?? null
            ),
            set: fn(?ValueObject $value) => [
                'value_object_value' => $value?->value
            ],
        );
    }

    public function entity(): HasOne
    {
        return $this->hasOne(TestModel2::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(TestModel2::class);
    }
}

class TestModel2 extends Model
{
    use Sushi;

    public static array $rows = [];

    public function getRows(): array
    {
        return static::$rows;
    }

    protected array $schema = [
        'id' => 'integer',
        'property' => 'string',
        'test_model1_id' => 'integer',
    ];

    public function parentEntity(): BelongsTo
    {
        return $this->belongsTo(TestModel1::class, 'test_model1_id', 'id');
    }
}

class Entity1
{
    /**
     * @param CollectionInterface<Entity2>|null $entities
     */
    public function __construct(
        #[Id]
        private ?int $id,
        private mixed $property = null,
        private mixed $valueObject = null,
        private ?Entity2 $entity = null,
        private ?CollectionInterface $entities = null,
        private mixed $date = null,
        private mixed $testEnum = null
    ) {
        $this->entities = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProperty(): mixed
    {
        return $this->property;
    }

    public function setProperty(mixed $property): void
    {
        $this->property = $property;
    }

    public function getValueObject(): mixed
    {
        return $this->valueObject;
    }

    public function setValueObject(mixed $valueObject): void
    {
        $this->valueObject = $valueObject;
    }

    public function setEntity(?Entity2 $entity): void
    {
        $this->entity = $entity;
    }

    public function getEntity(): ?Entity2
    {
        return $this->entity;
    }

    /**
     * @return CollectionInterface<Entity2>|null
     */
    public function getEntities(): ?CollectionInterface
    {
        return $this->entities;
    }

    public function getDate(): mixed
    {
        return $this->date;
    }

    public function setDate(mixed $date): void
    {
        $this->date = $date;
    }

    public function getTestEnum(): mixed
    {
        return $this->testEnum;
    }

    public function setTestEnum(mixed $testEnum): void
    {
        $this->testEnum = $testEnum;
    }
}

class Entity2
{
    private ?Entity1 $parentEntity;

    public function __construct(
        #[Id]
        private ?int $id,
        private mixed $property = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setProperty(mixed $property): void
    {
        $this->property = $property;
    }

    public function getProperty(): mixed
    {
        return $this->property;
    }

    public function getParentEntity(): ?Entity1
    {
        return $this->parentEntity;
    }
}

enum Enum: string
{
    case VALUE_1 = 'value_1';
    case VALUE_2 = 'value_2';
    case VALUE_3 = 'value_3';
}

class ValueObject
{
    public function __construct(public readonly mixed $value)
    {
    }
}
