<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class JsonBehaviorTest extends TestCase
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

    public function testSelectInOptions()
    {
        $query = $this->Users->find('json', [
          'json.fields' => [
            'string@attributes',
            'integer@attributes',
            'boolean@attributes',
            'null@attributes',
            'decimal@attributes',
            'float@attributes',
            'array@attributes',
            'object@attributes',
            'deep@attributes'
          ]
        ]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->first();//->toArray();

        $this->assertEquals([
            'attributes_string' => 'string1',
            'attributes_integer' => (int) 10,
            'attributes_boolean' => true,
            'attributes_null' => null,
            'attributes_decimal' => (float) 1.2,
            'attributes_float' => (int) 120000,
            'attributes_array' => [
                    (int) 0 => 'a',
                    (int) 1 => 'b'
            ],
            'attributes_object' => [
                    'a' => 'a',
                    'b' => 'b'
            ],
            'attributes_deep' => [
                    'key' => 'deepkey1'
            ]
        ], $result);
    }

    public function testSelectWithChainingMethodAndModel()
    {
        $query = $this->Users
          ->find('json')
          ->jsonSelect('deep.key@Users.attributes', '-');

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();

        $this->assertEquals([
          [
            'Users-attributes-deep-key' => 'deepkey1'
          ],
          [
            'Users-attributes-deep-key' => null
          ],
          [
            'Users-attributes-deep-key' => 'deepkey2'
          ]
        ], $result);
    }

    public function testWhereInOptions()
    {
        $query = $this->Users->find('json', [
          'fields' => 'id',
          'json.conditions' => ['username@attributes' => 'test1']
        ]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereWithChaining()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['username@attributes' => 'test1']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereDeep()
    {
        $query = $this->Users
        ->find('json')
        ->select('id')
        ->jsonWhere(['deep.key@attributes' => 'deepkey1']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereOnEmailValue()
    {
        $query = $this->Users
        ->find('json')
        ->select('id')
        ->jsonWhere(['email@attributes' => 'test1@liqueurdetoile.com']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereWithStringAndLike()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['username@attributes LIKE' => '%1']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereInteger()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['integer@attributes' => 10]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereDecimal()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['decimal@attributes' => 1.2]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereFloat()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['float@attributes' => 12e+14]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 3]
        ], $result);
    }

    public function testWhereBoolean()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['boolean@attributes' => true]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereNull()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['null@attributes' => null]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereArray()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['array@attributes' => ['a','b']]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereObject()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['object@attributes' => ['a'=>'a','b'=>'b']]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereNot()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere([
            'NOT' => [
              'username@attributes' => 'test2',
              'deep.key@attributes' => 'deepkey2'
            ]
          ]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereOr()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere([
            'OR' => [
              'username@attributes' => 'test2',
              'deep.key@attributes' => 'deepkey2'
            ]
          ]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2],
          ['id' => 3]
        ], $result);
    }

    public function testWhereSQL()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere('username@attributes NOT IN (\'"test2"\', \'"test3"\')');

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereNotSQL()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['not' => 'username@attributes NOT IN (\'"test2"\', \'"test3"\')']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2],
          ['id' => 3]
        ], $result);
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
          ]
        ], $user->attributes);
    }
}
