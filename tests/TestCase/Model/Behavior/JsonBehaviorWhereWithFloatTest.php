<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class JsonBehaviorWhereWithFloatTest extends TestCase
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

    public function testWhereDecimalWithFormulaInFieldName()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['decimal@attributes <' => 100]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereDecimalWithFormulaInExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($q) {
              return $q->lt('decimal@attributes', 100);
          });

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 1]
        ], $result);
    }

    public function testWhereDecimalWithFormulaInExpressionNotEq()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($q) {
              return $q->notEq('decimal@attributes', 100);
          });

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

    public function testWhereFloatWithFormulaInFieldname()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(['float@attributes >' => 12e+10]);

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 3]
        ], $result);
    }

    public function testWhereFloatWithFormulaInExpression()
    {
        $query = $this->Users
          ->find('json')
          ->select('id')
          ->jsonWhere(function ($q) {
              return $q->gt('float@attributes', 12e+10);
          });

        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals([
          ['id' => 3]
        ], $result);
    }
}
