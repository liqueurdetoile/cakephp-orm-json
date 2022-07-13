<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\ORM\JsonEntity;

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

    public function testAccessibility(): void
    {
        $object = $this->Objects->newEntity(['attributes' => ['test' => true]]);

        $object->setAccess('*', true);
        $this->assertTrue($object->isAccessible('attributes'));
        $this->assertTrue($object->isAccessible('attributes->test'));
        $this->assertTrue($object->isAccessible('test@attributes'));

        $object->setAccess('*', false);
        $this->assertFalse($object->isAccessible('attributes'));
        $this->assertFalse($object->isAccessible('attributes->test'));
        $this->assertFalse($object->isAccessible('test@attributes'));
        $object->set('attributes->test', false, ['guard' => true]);
        $this->assertTrue($object->get('attributes->test'));

        $object->setAccess('attributes', true);
        $this->assertTrue($object->isAccessible('attributes'));
        $this->assertTrue($object->isAccessible('attributes->test'));
        $this->assertTrue($object->isAccessible('test@attributes'));

        $object->setAccess('attributes->test', false);
        $this->assertTrue($object->isAccessible('attributes'));
        $this->assertFalse($object->isAccessible('attributes->test'));
        $this->assertFalse($object->isAccessible('test@attributes'));

        $object->setAccess('attributes', false);
        $this->assertFalse($object->isAccessible('attributes'));
        $this->assertFalse($object->isAccessible('attributes->test'));
        $this->assertFalse($object->isAccessible('test@attributes'));

        $object->setAccess('attributes->test', true);
        $this->assertFalse($object->isAccessible('attributes'));
        $this->assertTrue($object->isAccessible('attributes->test'));
        $this->assertTrue($object->isAccessible('test@attributes'));
    }

    public function testDirtinessHandling(): void
    {
        $object = $this->Objects->newEntity(['attributes' => ['test' => true, 'test2' => true]]);

        $this->assertTrue($object->isDirty());
        $this->assertTrue($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->clean();
        $this->assertFalse($object->isDirty());
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->set('attributes->test', false);
        $this->assertTrue($object->isDirty());
        $this->assertTrue($object->isDirty('attributes'));
        $this->assertTrue($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertTrue($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->setDirty('attributes->test', false);
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));

        $object->set('attributes->test', false);
        $object->setDirty('attributes', false);
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->isDirty('attributes->test'));
        $this->assertFalse($object->isDirty('attributes->test2'));
        $this->assertFalse($object->isDirty('test@attributes'));
        $this->assertFalse($object->isDirty('test2@attributes'));
    }

    public function testHas(): void
    {
        $object = $this->Objects->newEntity(['attributes' => ['test' => true, 'test2' => null]]);

        $this->assertTrue($object->has('attributes'));
        $this->assertTrue($object->has('attributes->test'));
        $this->assertFalse($object->has('attributes->test2'));
        $this->assertFalse($object->has('attributes->test3 '));
    }

    public function testDelete(): void
    {
        $object = $this->Objects->newEntity(['attributes' => ['test' => true, 'test2' => null]]);
        $object->clean();

        $this->assertFalse($object->isDirty('attributes'));
        $object->delete('attributes->test3');
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertTrue($object->has('attributes->test'));
        $object->delete('attributes->test2');
        $this->assertTrue($object->isDirty('attributes'));
        $this->assertFalse($object->has('attributes->test2'));
    }

    public function testUnset(): void
    {
        $object = $this->Objects->newEntity(['attributes' => ['test' => true, 'test2' => null]]);
        $object->clean();

        $this->assertFalse($object->isDirty('attributes'));
        $object->unset('attributes->test3');
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertTrue($object->has('attributes->test'));
        $object->unset('attributes->test2');
        $this->assertFalse($object->isDirty('attributes'));
        $this->assertFalse($object->has('attributes->test2'));
    }

    /**
     * This test is merely here to ensure that driver is doing well in saving, updating and removing data
     */
    public function testCreateUpdateAndDelete()
    {
        $object = $this->Objects->newEntity(['attributes' => ['test' => true]]);
        $object = $this->Objects->saveOrFail($object);
        $this->assertTrue($object->get('test@attributes'));
        $this->assertNotEmpty($object);
        $object->set('test@attributes', false);
        $this->assertFalse($object->get('test@attributes'));
        $object = $this->Objects->saveOrFail($object);
        $this->Objects->deleteOrFail($object);
    }

    public function testMagicMethodsAndSetters()
    {
        $object = $this->Objects->newEntity([]);
        $this->assertInstanceOf(JsonEntity::class, $object);
        // test magic methods
        $object->id = 'abc';
        $object->{'test@attributes'} = true;
        $this->assertEquals('abc', $object->id);
        $this->assertTrue($object->{'test@attributes'});
        $this->assertTrue(isset($object->{'test@attributes'}));
        unset($object->id);
        unset($object->{'test@attributes'});
        $this->assertFalse(isset($object->id));
        $this->assertFalse(isset($object->{'test@attributes'}));
    }
}
