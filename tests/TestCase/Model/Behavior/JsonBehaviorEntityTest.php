<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class JsonBehaviorEntityTest extends TestCase
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

    public function testMarshalWithNewEntityAndDatField()
    {
        $user = $this->Users->newEntity([
          'test@attributes' => 'test',
          'nested.test@attributes' => 'deep'
        ]);

        $this->assertEquals([
          'test' => 'test',
          'nested' => [
              'test' => 'deep'
          ]
        ], $user->attributes);
    }

    public function testWithPatchEntity()
    {
        $user = $this->Users->get(3);
        $user = $this->Users->patchEntity($user, [
            'test@attributes' => 'test',
            'nested.test@attributes' => 'deep'
        ]);

        $this->assertEquals([
          'test' => 'test',
          'nested' => [
              'test' => 'deep'
          ]
        ], $user->attributes);
    }

    public function testWithPatchEntityAndMerge()
    {
        $user = $this->Users->get(3);
        $user = $this->Users->patchEntity($user, [
          'test@attributes' => 'test',
          'nested.test@attributes' => 'deep'
        ])->jsonMerge();

        $this->assertEquals([
          "username" => "test3",
          "email" => "test3@liqueurdetoile.com",
          "boolean" => false,
          "null" => false,
          "float" => 1.2E+15,
          "deep" => [
              "key" => "deepkey2"
          ],
          'test' => 'test',
          'nested' => [
              'test' => 'deep'
          ],
          'group' => 2
        ], $user->attributes);
    }
}
