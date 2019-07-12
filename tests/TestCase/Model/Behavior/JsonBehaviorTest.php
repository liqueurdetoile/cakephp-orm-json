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

    public function testSelectWithAliases()
    {
        $query = $this->Users
          ->find('json')
          ->jsonSelect(['deepkey' => 'deep.key@Users.attributes']);

        $result = $query->enableHydration(false)->first();
        $this->assertEquals(['deepkey'=>'deepkey1'], $result);
    }

    public function testSelectMixingFieldsAndDatfields()
    {
        $query = $this->Users
        ->find('json')
        ->jsonSelect(['id', 'deep.key@Users.attributes'], '_', true);

        $result = $query->first()->toArray();
        $this->assertEquals(['id'=>1,'users_attributes_deep_key'=>'deepkey1'], $result);
    }

    public function testSelectWithDotAsSeparatorAndEntities()
    {
        $query = $this->Users
          ->find('json')
          ->jsonSelect(['deep.key@Users.attributes'], '.', true);

        $result = $query->first()->toArray();
        $this->assertEquals(['users.attributes.deep.key'=>'deepkey1'], $result);
    }

    public function testSelectWithDottedAliases()
    {
        $query = $this->Users
          ->find('json')
          ->jsonSelect(['deep.key' => 'deep.key@Users.attributes']);

        $result = $query->enableHydration(false)->first();
        $this->assertEquals(['deep.key'=>'deepkey1'], $result);
    }

    public function testSelectWithDotAsSeparatorAndArrayOfEntities()
    {
        $query = $this->Users
          ->find('json')
          ->jsonSelect(['deep.key@attributes'], '.');

        $result = $query->toArray();
        $this->assertEquals(true, is_array($result));
        $this->assertEquals('deepkey1', $result[0]->get('attributes.deep.key'));
        $this->assertEquals(null, $result[1]->get('attributes.deep.key'));
        $this->assertEquals('deepkey2', $result[2]->get('attributes.deep.key'));
    }

    public function testSelectWithDotAsSeparatorAndHydrationDisabled()
    {
        $query = $this->Users
          ->find('json')
          ->jsonSelect(['deep.key@Users.attributes'], '.', true);

        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals('deepkey1', $result[0]['users.attributes.deep.key']);
        $this->assertEquals(null, $result[1]['users.attributes.deep.key']);
        $this->assertEquals('deepkey2', $result[2]['users.attributes.deep.key']);

        $result = $query->enableHydration(false)->first();
        $this->assertEquals(['users.attributes.deep.key'=>'deepkey1'], $result);
    }

    public function testSelectWithAssocOnEntities()
    {
        $query = $this->Users
        ->find('json')
        ->jsonSelect(['deep.key@Users.attributes'], false, true);

        $result = $query->first();
        $this->assertEquals('deepkey1', $result->attributes['deep']['key']);
    }

    public function testSelectWithAssocOnEntitiesAndMultipleFieldsAndHydrationDisabled()
    {
        $query = $this->Users
        ->find('json')
        ->jsonSelect(['deep.key@Users.attributes', 'string@attributes'], false, true);

        $result = $query->first();
        $this->assertEquals([
          'string' => 'string1',
          'deep' => [
            'key' => 'deepkey1'
          ]
        ], $result->attributes);
    }

    /** @group current */
    public function testSelectWithAssocOnEntitiesAndMultipleFields()
    {
        $query = $this->Users
        ->find('json')
        ->jsonSelect(['deep.key@Users.attributes', 'string@attributes'], false, true);

        $result = $query->enableHydration(false)->first();
        $this->assertEquals([
          'string' => 'string1',
          'deep' => [
            'key' => 'deepkey1'
          ]
        ], $result['attributes']);
    }

    public function testSelectWithAssocOnArray()
    {
        $query = $this->Users
        ->find('json')
        ->jsonSelect(['deep.key@Users.attributes'], false, true);

        $result = $query->enableHydration(false)->first();
        $this->assertEquals('deepkey1', $result['attributes']['deep']['key']);
    }

    /** @group current */
    public function testSelectWithAssocOnDottedAlias()
    {
        $query = $this->Users
        ->find('json')
        ->jsonSelect(['my.key' => 'deep.key@Users.attributes'], false);

        $result = $query->enableHydration(false)->first();
        $this->assertEquals('deepkey1', $result['my']['key']);
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

    public function testWhereIntegerWithComparison()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['integer@attributes <' => 100]);

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

    public function testWhereWithMixedFields()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere([
            'id >' => 1,
            'group@attributes' => 1
          ]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2]
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
          ],
          'group' => 2
        ], $user->attributes);
    }
}
