<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class driverOrderTest extends TestCase
{
    public $Objects;
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\SortObjectsFixture'];

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

    public function orderData()
    {
        return [
          [
            ['string@attributes' => 'ASC'],
            [3, 1, 2]
          ],

          [
            ['deep.key@attributes' => 'ASC'],
            [3, 1, 2]
          ],

          [
            ['string@attributes' => 'DESC'],
            [2, 1, 3]
          ],

          [
            ['same@attributes', 'string@attributes'],
            [1, 2, 3]
          ],

          [
            'integer@attributes',
            [3, 1, 2]
          ],

          [
            'boolean@attributes',
            [3, 1, 2]
          ],

          [
            'decimal@attributes',
            [3, 1, 2]
          ],

          [
            'float@attributes',
            [3, 1, 2]
          ],

          [
            ['maybeNull@attributes' => 'DESC'],
            [2, 1, 3]
          ],

          [
            ['id' => 'DESC'],
            [3, 2, 1]
          ],

          [
            ['same@attributes', 'id' => 'DESC'],
            [2, 1, 3]
          ],
        ];
    }

    /** @dataProvider orderData */
    public function testOrderWithAutofields($order, $expected)
    {
        $query = $this->Objects->find()->order($order);
        $results = $query->all();
        $this->assertEquals($expected, $results->map(function ($r) {
            return $r->id;
        })->toArray());
    }

    /** @dataProvider orderData */
    public function testOrderWithSelectedField($order, $expected)
    {
        $query = $this->Objects->find('id')->order($order);
        $results = $query->all();
        $this->assertEquals($expected, $results->map(function ($r) {
            return $r->id;
        })->toArray());
    }
}
