<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;

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
          'json.fields' => 'username@attributes'
        ]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();

        $this->assertEquals([
          [
            'attributes_username' => '"test1"'
          ],
          [
            'attributes_username' => '"test2"'
          ],
          [
            'attributes_username' => '"test3"'
          ]
        ], $result);
    }

    public function testSelectWithChainingMethodAndModel()
    {
        $query = $this->Users
          ->find('json')
          ->jsonselect('deep.key@Users.attributes', '-');

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();

        $this->assertEquals([
          [
            'Users-attributes-deep-key' => '"deepkey1"'
          ],
          [
            'Users-attributes-deep-key' => null
          ],
          [
            'Users-attributes-deep-key' => '"deepkey2"'
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
          ->jsonwhere(['username@attributes' => 'test1']);

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
        ->jsonwhere(['deep.key@attributes' => 'deepkey1']);

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
        ->jsonwhere(['email@attributes' => 'test1@liqueurdetoile.com']);

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
          ->jsonwhere(['username@attributes LIKE' => '%1']);

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
          ->jsonwhere(['integer@attributes' => 10]);

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
          ->jsonwhere(['decimal@attributes' => 1.2]);

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
          ->jsonwhere(['float@attributes' => 12e+14]);

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
          ->jsonwhere(['boolean@attributes' => true]);

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
          ->jsonwhere(['null@attributes' => null]);

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
          ->jsonwhere(['array@attributes' => ['a','b']]);

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
          ->jsonwhere(['object@attributes' => ['a'=>'a','b'=>'b']]);

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
          ->jsonwhere([
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
          ->jsonwhere([
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
          ->jsonwhere('username@attributes NOT IN (\'"test2"\', \'"test3"\')');

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
          ->jsonwhere(['not' => 'username@attributes NOT IN (\'"test2"\', \'"test3"\')']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2],
          ['id' => 3]
        ], $result);
    }
}
