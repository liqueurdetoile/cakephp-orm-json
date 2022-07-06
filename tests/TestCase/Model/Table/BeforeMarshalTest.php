<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Model\Table\ObjectsTable;

class BeforeMarshalTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable
     */
    public $Objects = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => ObjectsTable::class]);
    }

    public function tearDown(): void
    {
        $this->Objects = null;
        parent::tearDown();
    }

    public function testWithNewEntity(): void
    {
        $e = $this->Objects->newEntity([
          'test@attributes' => true,
        ]);

        $this->assertTrue($e->attributes['test']);
    }

    public function testWithPatchEntity(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([]));

        $this->assertEmpty($e->attributes);
        $this->Objects->patchEntity($e, ['test@attributes' => true]);

        $this->assertTrue($e->attributes['test']);
    }

    /**
     * When patching, default behavior is to merge json data
     */
    public function testWithPatchEntityDefaultBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true]);

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->assertTrue($e->jsonGet('deep.keep@at2'));
    }

    /**
     * Merging can be disabled permanently on table for all fields
     */
    public function testWithPatchEntityUpdatedBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->Objects->configureJsonFields(['jsonReplace' => true]);
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true]);

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertEmpty($e->jsonGet('deep.keep@attributes'));
        $this->assertEmpty($e->jsonGet('deep.keep@at2'));
    }

    /**
     * Merging can be disabled permanently on table for a targetted field
     */
    public function testWithPatchEntityUpdatedTargettedBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->Objects->configureJsonFields(['jsonReplace' => true, 'jsonFields' => ['at2']]);
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true]);

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->assertEmpty($e->jsonGet('deep.keep@at2'));
    }

    /**
     * Merging with patchEntity can be forced to be a replacement
     */
    public function testWithPatchEntityReplaceAllBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true], ['jsonReplace' => true]);

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertEmpty($e->jsonGet('deep.keep@attributes'));
        $this->assertEmpty($e->jsonGet('deep.keep@at2'));
    }

    /**
     * Merging with patchEntity can be forced to be a replacement
     */
    public function testWithPatchEntityReplaceTargettedBehavior(): void
    {
        $e = $this->Objects->saveOrFail($this->Objects->newEntity([
          'deep.keep@attributes' => true,
          'deep.keep@at2' => true,
        ]));

        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->Objects->patchEntity($e, ['test@attributes' => true, 'test@at2' => true], ['jsonReplace' => true, 'jsonFields' => ['at2']]);

        $this->assertTrue($e->attributes['test']);
        $this->assertTrue($e->at2['test']);
        $this->assertTrue($e->jsonGet('deep.keep@attributes'));
        $this->assertEmpty($e->jsonGet('deep.keep@at2'));
    }
}
