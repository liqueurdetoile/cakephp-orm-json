<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class JsonBehaviorWhereTest extends TestCase
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

    public function testWhereWithExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($exp) {
              return $exp->eq('username@attributes', 'test1');
          });

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

    public function testWhereOnEmailValueWithInOperator()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['email@attributes IN' => ['test1@liqueurdetoile.com', 'test2@liqueurdetoile.com']]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1],
          ['id' => 2],
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

    public function testWhereWithStringAndNotLike()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['username@attributes NOT LIKE' => '%1']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2],
          ['id' => 3]
        ], $result);
    }

    public function testWhereWithStringAndEqual()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['username@attributes =' => 'test1']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereWithStringAndnotEqual()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['username@attributes !=' => 'test1']);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2],
          ['id' => 3]
        ], $result);
    }

    public function testWhereWithStringAndnotEqualWithExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($exp) {
              $conditions = $exp->and_(function ($and) {
                  return $and->eq('username@attributes', 'test1');
              });

              return $exp->not($conditions);
          });

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2],
          ['id' => 3]
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

    public function testWhereIntegerWithComparisonWithExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($exp) {
              return $exp->lt('integer@attributes', 100, 'integer');
          });

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
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

    public function testWhereBooleanInExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($q) {
              return $q->notEq('boolean@attributes', false);
          });

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

    public function testWhereArrayWithExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($exp) {
              return $exp->eq('array@attributes', ['a','b']);
          });

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

    public function testWhereNot()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere([
            'NOT' => [
              'OR' => [
                'username@attributes' => 'test2',
                'deep.key@attributes' => 'deepkey2'
              ]
            ]
          ]);

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

    public function testWhereNotSQLWithExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($q) {
              return $q->not('username@attributes NOT IN (\'"test2"\', \'"test3"\')');
          });

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

    public function testWhereWithMixedFieldsWithExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($q) {
              return $q->gt('id', 1)->eq('group@attributes', 1);
          });

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 2]
        ], $result);
    }
}
