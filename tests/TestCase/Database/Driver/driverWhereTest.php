<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class driverWhereTest extends TestCase
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

    public function whereData()
    {
        return [
// STRING
          [
            ['username@attributes' => 'test1'],
            [['id' => 1]]
          ],
          [
            function ($exp) {
                return $exp->eq('username@attributes', 'test1');
            },
            [['id' => 1]]
          ],
          [
            ['deep.key@attributes' => 'deepkey1'],
            [['id' => 1]]
          ],
          [
            ['email@attributes' => 'test1@liqueurdetoile.com'],
            [['id' => 1]]
          ],
          [
            ['email@attributes IN' => ['test1@liqueurdetoile.com', 'test2@liqueurdetoile.com']],
            [['id' => 1], ['id' => 2]]
          ],
          [
            function ($exp) {
                return $exp->in('email@attributes', ['test1@liqueurdetoile.com', 'test2@liqueurdetoile.com']);
            },
            [['id' => 1], ['id' => 2]]
          ],
          [
            ['username@attributes LIKE' => '%1'],
            [['id' => 1]]
          ],
          [
            ['username@attributes NOT LIKE' => '%1'],
            [['id' => 2], ['id' => 3]]
          ],
          [
            ['username@attributes =' => 'test1'],
            [['id' => 1]]
          ],
          [
            ['username@attributes !=' => 'test1'],
            [['id' => 2], ['id' => 3]]
          ],
          [
            function ($exp) {
                $conditions = $exp->and_(function ($and) {
                    return $and->eq('username@attributes', 'test1');
                });

                return $exp->not($conditions);
            },
            [['id' => 2], ['id' => 3]]
          ],
// INTEGER
          [
            ['integer@attributes' => 10],
            [['id' => 1]]
          ],
          [
            ['integer@attributes <' => 100],
            [['id' => 1]]
          ],
          [
            function ($exp) {
                return $exp->lt('integer@attributes', 100, 'integer');
            },
            [['id' => 1]]
          ],
// DECIMAL
          [
            ['decimal@attributes' => 1.2],
            [['id' => 1]]
          ],
          [
            ['decimal@attributes <' => 100],
            [['id' => 1]]
          ],
          [
            function ($q) {
                return $q->lt('decimal@attributes', 100);
            },
            [['id' => 1]]
          ],
          [
            function ($q) {
                return $q->notEq('decimal@attributes', 100);
            },
            [['id' => 1]]
          ],
// FLOAT
          [
            ['float@attributes' => 12e+14],
            [['id' => 3]]
          ],
          [
            ['float@attributes >' => 12e+10],
            [['id' => 3]]
          ],
          [
            function ($q) {
                return $q->gt('float@attributes', 12e+10);
            },
            [['id' => 3]]
          ],
// BOOLEAN
          [
            ['boolean@attributes' => true],
            [['id' => 1]]
          ],
          [
            function ($q) {
                return $q->notEq('boolean@attributes', false);
            },
            [['id' => 1]]
          ],
// NULL
          [
            ['null@attributes' => null],
            [['id' => 1]]
          ],
// ARRAY
          [
            ['array@attributes' => ['a','b']],
            [['id' => 1]]
          ],
          [
            function ($exp) {
                return $exp->eq('array@attributes', ['a','b']);
            },
            [['id' => 1]]
          ],
// OBJECT
          [
            ['object@attributes' => ['a'=>'a','b'=>'b']],
            [['id' => 1]]
          ],
// SQL FRAGMENT
          [
            'username@attributes NOT IN (\'"test2"\', \'"test3"\')',
            [['id' => 1]]
          ],
// CONJUNCTIONS
          [
            [
              'OR' => ['username@attributes' => 'test2', 'deep.key@attributes' => 'deepkey2']
            ],
            [['id' => 2], ['id' => 3]]
          ],
          [
            [
              'NOT' => [
                'OR' => [
                  'username@attributes' => 'test2',
                  'deep.key@attributes' => 'deepkey2'
                ]
              ]
            ],
            [['id' => 1]]
          ],
          [
            ['not' => 'username@attributes NOT IN (\'"test2"\', \'"test3"\')'],
            [['id' => 2], ['id' => 3]]
          ],
          [
            function ($q) {
                return $q->not('username@attributes NOT IN (\'"test2"\', \'"test3"\')');
            },
            [['id' => 2], ['id' => 3]]
          ],
          [
            [
              'id >' => 1,
              'group@attributes' => 1
            ],
            [['id' => 2]]
          ],
          [
            function ($q) {
                return $q->gt('id', 1)->eq('group@attributes', 1);
            },
            [['id' => 2]]
          ],
        ];
    }

    /** @dataProvider whereData */
    public function testWhereAsArray($filter, $expected)
    {
        $query = $this->Objects->find('id')->where($filter);
        $result = $query->enableHydration(false)->toArray();
        $this->assertEquals($expected, $result);
    }

    /** @dataProvider whereData */
    public function testWhereAsEntity($filter, $expected)
    {
        $query = $this->Objects->find('id')->where($filter);
        $result = $query->all()->map(function ($e) {
            return $e->toArray();
        });
        $this->assertEquals($expected, $result->toArray());
    }
}
