<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Model\Table\ObjectsTable;

class DatFieldBehaviorTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable
     */
    public $Objects = null;

    public function setUp(): void
    {
        parent::setUp();
        /** @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable $Objects */
        $Objects = TableRegistry::get('Objects', ['className' => ObjectsTable::class]);

        $this->Objects = $Objects;
    }

    public function tearDown(): void
    {
        unset($this->Objects);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testMarshalWithNewEntity(): void
    {
        $e = $this->Objects->newEntity([
          'test@attributes' => true,
        ]);

        $this->assertTrue($e->attributes['test']);
    }

    public function testMarshalWithJsonType(): void
    {
        $this->Objects->getSchema()->setJsonTypes('at2->date', 'date');

        $e = $this->Objects->newEntity([
          'at2->date' => '2021-01-31',
        ]);

        $this->assertInstanceof(FrozenDate::class, $e->{'at2->date'});
        $this->assertEquals('31/01/2021', $e->{'at2->date'}->format('d/m/Y'));
    }

    public function testMarshalWithJsonTypeAsCallback(): void
    {
        $this->Objects->getSchema()->setJsonTypes('at2->date', [
          'marshal' => function ($d) {
            return FrozenDate::createFromFormat('d/m/Y', $d);
          },
        ]);

        $e = $this->Objects->newEntity([
          'at2->date' => '31/01/2021',
        ]);

        $this->assertInstanceof(FrozenDate::class, $e->{'at2->date'});
        $this->assertEquals('31/01/2021', $e->{'at2->date'}->format('d/m/Y'));
    }

    public function testMarshalWithTransientJsonType(): void
    {
        $e = $this->Objects->newEntity([
          'at2->date' => '2021-01-31',
        ], ['jsonTypeMap' => ['at2->date' => 'date']]);

        $this->assertInstanceof(FrozenDate::class, $e->{'at2->date'});
        $this->assertEquals('31/01/2021', $e->{'at2->date'}->format('d/m/Y'));

        $e = $this->Objects->newEntity([
          'at2->date' => '2021-01-31',
        ]);

        $this->assertNotInstanceof(FrozenDate::class, $e->{'at2->date'});
    }

    public function testMarshalWithPatchEntity(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([]));

        $this->assertEmpty($e->attributes);
        $this->Objects->patchEntity($e, ['test@attributes' => true]);

        $this->assertTrue($e->attributes['test']);
    }

    /**
     * When patching, default behavior is to replace json data
     */
    public function testMarshalWithPatchEntityDefaultBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->get('deep.keep@attributes'));
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true]);

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertNull($e->get('deep.keep@attributes'));
        $this->assertNull($e->get('deep.keep@at2'));
    }

    /**
     * Merging can be enabled through query
     */
    public function testMarshalWithPatchEntityUpdatedBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->get('deep.keep@attributes'));
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true], ['jsonMerge' => true]);
        // Aftermarshal event is not available until CakePHP 4. Needs to use old school way
        /** @phpstan-ignore-next-line */
        if (COMPAT_MODE) {
            $e->jsonMerge();
        }

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertTrue($e->get('deep.keep@attributes'));
        $this->assertTrue($e->get('deep.keep@at2'));
    }

    /**
     * Merging can be enabled for a targetted field
     */
    public function testMarshalWithPatchEntityUpdatedTargettedBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->get('deep.keep@attributes'));
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true], ['jsonMerge' => ['attributes']]);
        // Aftermarshal event is not available until CakePHP 4. Needs to use old school way
        /** @phpstan-ignore-next-line */
        if (COMPAT_MODE) {
            $e->jsonMerge('attributes');
        }

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertTrue($e->get('deep.keep@attributes'));
        $this->assertNull($e->get('deep.keep@at2'));
    }

    public function testJsonTypeMap(): void
    {
        $this->Objects->getSchema()->setJsonTypes('at2->date', 'date');

        $e = $this->Objects->newEntity([
          'at2' => ['date' => new FrozenDate('2021-01-31')],
        ]);

        $e = $this->Objects->saveOrFail($e);
        $e = $this->Objects->get($e->id);

        $this->assertInstanceof(FrozenDate::class, $e->{'at2->date'});
        $this->assertEquals('31/01/2021', $e->{'at2->date'}->format('d/m/Y'));
    }

    public function testTransientJsonTypeMap(): void
    {
        $e = $this->Objects->newEntity([
          'at2' => ['date' => new FrozenDate('2021-01-31')],
        ]);

        $e = $this->Objects->saveOrFail($e, ['jsonTypeMap' => ['at2->date' => 'date']]);
        // With transient types, map is removed after each call
        $e = $this->Objects->get($e->id);
        $this->assertNotInstanceof(FrozenDate::class, $e->{'at2->date'});

        $e = $this->Objects->get($e->id, ['jsonTypeMap' => ['at2->date' => 'date']]);
        $this->assertInstanceof(FrozenDate::class, $e->{'at2->date'});
        $this->assertEquals('31/01/2021', $e->{'at2->date'}->format('d/m/Y'));

        $e = $this->Objects->get($e->id);
        $this->assertNotInstanceof(FrozenDate::class, $e->{'at2->date'});
    }
}
