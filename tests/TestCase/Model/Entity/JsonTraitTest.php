<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Entity;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Datasource\EntityTrait;
use Lqdt\OrmJson\Model\Entity\JsonTrait;

class User extends Entity
{
    use EntityTrait;
    use JsonTrait;
}

class JsonTraitTest extends TestCase
{
    public $user; // Mock up entity
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
        $users = TableRegistry::get('Users');
        $users->addBehavior('Lqdt\OrmJson\Model\Behavior\JsonBehavior');
        $users->setEntityClass('Lqdt\OrmJson\Test\TestCase\Model\Entity\User');
        $this->user = $users->find('json')->first();
        $this->Users = $users;
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

    public function testConstructor()
    {
        $this->assertInstanceOf('Cake\ORM\Entity', $this->user);
        $this->assertEquals(1, $this->user->id);
        $this->assertEquals([
          'Lqdt\OrmJson\Model\Entity\JsonTrait' => 'Lqdt\OrmJson\Model\Entity\JsonTrait',
          'Cake\Datasource\EntityTrait' => 'Cake\Datasource\EntityTrait'
        ], class_uses($this->user));
    }

    public function testjsonGetAsObject()
    {
        $attributes = $this->user->jsonGet('attributes');

        $this->assertEquals(
          [
              'string1',
              10,
              true,
              null,
              1.2,
              ['a','b'],
              'deepkey1'
          ],
          [
              $attributes->string,
              $attributes->integer,
              $attributes->boolean,
              $attributes->null,
              $attributes->decimal,
              $attributes->array,
              $attributes->deep->key,
          ]
        );
    }

    public function testjsonGetAsArray()
    {
        $this->assertEquals(
          [
              'string1',
              10,
              true,
              null,
              1.2,
              ['a','b'],
              ['a' => 'a', 'b' => 'b'],
              'deepkey1'
          ],
          [
              $this->user->jsonGet('string@attributes', true),
              $this->user->jsonGet('integer@attributes', true),
              $this->user->jsonGet('boolean@attributes', true),
              $this->user->jsonGet('null@attributes', true),
              $this->user->jsonGet('decimal@attributes', true),
              $this->user->jsonGet('array@attributes', true),
              $this->user->jsonGet('object@attributes', true),
              $this->user->jsonGet('deep.key@attributes', true),
          ]
        );
    }

    public function test_jsonSetWithString()
    {
        $this->user->jsonSet('blap@attributes', 'blap');
        $this->assertEquals(
          'blap',
          $this->user->jsonGet('blap@attributes', true)
        );
        $this->Users->save($this->user);
        $testSave = $this->Users->find('json')->first();
        $this->assertEquals(
          'blap',
          $testSave->jsonGet('blap@attributes', true)
        );
    }

    public function test_jsonSetWithNull()
    {
        $this->user->jsonSet('blap@attributes', null);
        $this->assertEquals(
          null,
          $this->user->jsonGet('blap@attributes', true)
        );
        $this->Users->save($this->user);
        $testSave = $this->Users->find('json')->first();
        $this->assertEquals(
          null,
          $testSave->jsonGet('blap@attributes', true)
        );
    }

    public function test_jsonSetWithArray()
    {
        $this->user->jsonSet([
          'blap@attributes' => 'blap',
          'string@attributes' => 'blap'
        ]);

        $this->assertEquals(
          'blap',
          $this->user->jsonGet('blap@attributes', true)
        );

        $this->assertEquals(
          'blap',
          $this->user->jsonGet('string@attributes', true)
        );
    }

    public function test_jsonIsset()
    {
        $this->assertEquals(
          true,
          $this->user->jsonIsset('username@attributes')
        );

        $this->assertEquals(
          false,
          $this->user->jsonGet('blap@attributes', true)
        );
    }

    public function test_jsonUnsetWithArray()
    {
        $this->assertEquals(
          true,
          $this->user->jsonIsset('deep.key@attributes', true)
        );

        $this->assertEquals(
          true,
          $this->user->jsonGet('decimal@attributes', true)
        );

        $this->user->jsonUnset([
          'deep.key@attributes',
          'decimal@attributes'
        ]);

        $this->assertEquals(
          false,
          $this->user->jsonIsset('deep.key@attributes')
        );

        $this->assertEquals(
          false,
          $this->user->jsonGet('decimal@attributes', true)
        );
    }
}
