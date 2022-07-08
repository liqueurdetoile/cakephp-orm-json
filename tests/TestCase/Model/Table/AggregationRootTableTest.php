<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\TestCase\DataGenerator;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class AggregationRootTableTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    public $Objects;
    public $data = [];
    public $objects;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable']);
        $generator = new DataGenerator();
        $this->data = $generator
          ->faker('attributes.mixed', 'randomElement', [null, true, false, 'bar', 'baz'])
          ->faker('attributes.boolean', 'randomElement', [true, false])
          ->faker('attributes.number', 'randomElement', [0,1,2,3,4])
          ->faker('attributes.float', 'randomElement', [0, 0.1, 0.2, 0.3, 0.4])
          ->faker('attributes.string', 'randomElement', ['foo', 'bar', 'baz', 'boo'])
          ->faker('attributes.date', 'randomElement', ['2019-06-01', '2020-06-01', '2020-07-01'])
          ->faker('attributes.really.deep.boolean', 'randomElement', [true, false])
          ->faker('attributes.really.deep.number', 'randomElement', [0,1,2,3,4])
          ->faker('attributes.really.deep.float', 'randomElement', [0, 0.1, 0.2, 0.3, 0.4])
          ->faker('attributes.really.deep.string', 'randomElement', ['foo', 'bar', 'baz', 'boo'])
          ->faker('attributes.really.deep.date', 'randomElement', ['2019-06-01', '2020-06-01', '2020-07-01'])
          ->generate(50);
          $objects = $this->Objects->newEntities($this->data);
          $this->objects = $this->Objects->saveManyOrFail($objects);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Objects);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testGroupBy(): void
    {
        $q = $this->Objects->find()
          ->select(['string' => 'attributes->string'])
          ->order(['string' => 'DESC'])
          ->group('string')
          ->distinct();

        $data = $q->all()->map(function ($o) {
            return $o->string;
        })->toArray();

        $this->assertSame(['foo', 'boo', 'baz', 'bar'], $data);
    }

    public function testHaving(): void
    {
        $q = $this->Objects->find();
        $q
          ->select(['string' => 'attributes->string', 'count' => $q->func()->count('*')])
          ->group('string')
          ->having(['string' => 'foo'])
          ->distinct();

        $data = $q->all()->map(function ($o) {
            return $o->toArray();
        })->toArray();

        $this->assertSame([['string' => 'foo', 'count' => 9]], $data);
    }
}
