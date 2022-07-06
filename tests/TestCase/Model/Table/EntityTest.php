<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\ORM\ObjectEntity;

class EntityTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    public $Objects;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Objects);
        TableRegistry::clear();

        parent::tearDown();
    }

    /**
     * This test is merely here to ensure that driver is doing well in saving, updating and removing data
     */
    public function testCreateUpdateAndDelete()
    {
        $object = $this->Objects->newEntity(['attributes' => ['test' => true]]);
        $object = $this->Objects->saveOrFail($object);
        $this->assertTrue($object->jsonGet('test@attributes'));
        $this->assertNotEmpty($object);
        $object->jsonSet('test@attributes', false);
        $object = $this->Objects->saveOrFail($object);
        $this->Objects->deleteOrFail($object);
    }

    public function testMagicMethodsAndSetters()
    {
        $object = $this->Objects->newEntity([]);
        $this->assertInstanceOf(ObjectEntity::class, $object);
        // test magic methods
        $object->id = 'abc';
        $object->{'test@attributes'} = true;
        $this->assertEquals('abc', $object->id);
        $this->assertTrue($object->{'test@attributes'});
        unset($object->id);
        unset($object->{'test@attributes'});
        $this->assertFalse(isset($object->id));
        $this->assertFalse(isset($object->{'test@attributes'}));
    }
}
