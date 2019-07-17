<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class JsonBehaviorQueryConstructorTest extends TestCase
{
    public $Users; // Mock up model
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\UsersFixture'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Users = TableRegistry::get('Users');
        $this->Users->addBehavior('Lqdt\OrmJson\Model\Behavior\JsonBehavior');
        $this->Users->setEntityClass('Lqdt\OrmJson\Test\TestCase\Model\Entity\User');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Users);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testQueryConstructor()
    {
        $query = $this->Users->jsonQuery();
        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $query = $this->Users->find('json');
        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
    }
}
