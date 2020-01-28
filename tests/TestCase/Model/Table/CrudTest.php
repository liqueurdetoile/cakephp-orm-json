<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;

class CrudTest extends TestCase
{
    public $Objects;
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\ObjectsFixture'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Objects);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testCreate()
    {
        $object = $this->Objects->newEntity();
        $object->set('attributes', ['test' => true]);
        $object = $this->Objects->saveOrFail($object);
        $this->assertFalse($object->isNew());
    }

    public function testUpdate()
    {
        $object = $this->Objects->get(1);
        $object->set('attributes', ['test' => true]);
        $object = $this->Objects->saveOrFail($object);
        $this->assertTrue($object->jsonGet('test@attributes'));
    }

    public function testDelete()
    {
        $object = $this->Objects->get(1);
        $object = $this->Objects->deleteOrFail($object);
        $this->assertEquals(2, $this->Objects->find()->count());
    }
}
