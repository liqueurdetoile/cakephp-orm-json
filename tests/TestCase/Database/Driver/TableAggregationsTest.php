<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\Collection\Collection;
use Cake\ORM\TableRegistry;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class TableAggregationsTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable
     */
    public $Objects;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        /** @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable $Objects */
        $Objects = TableRegistry::get('Objects', ['className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable']);
        $this->Objects = $Objects;
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

    public function testGroup(): void
    {
        $data = $this->generator
          ->clear()
          ->faker('attributes.string', 'randomElement', ['foo', 'bar', 'baz'])
          ->generate(50);

        $this->Objects->saveManyOrFail($this->Objects->newEntities($data));
        $data = (new Collection($data))->countBy('attributes.string')->toArray();

        $q = $this->Objects->find();

        $results = $q
          ->select(['count' => $q->func()->count('*'), 'v' => 'attributes->string'])
          ->group('attributes->string')
          ->all();

        foreach ($results as $s) {
            $this->assertEquals($data[$s->v], $s->count);
        }
    }

    public function testGroupWithAlias(): void
    {
        $data = $this->generator
          ->clear()
          ->faker('attributes.string', 'randomElement', ['foo', 'bar', 'baz'])
          ->generate(50);

        $this->Objects->saveManyOrFail($this->Objects->newEntities($data));

        $q = $this->Objects->find();

        $results = $q
          ->select(['string' => 'attributes->string'])
          ->group('string')
          ->order('string')
          ->all()
          ->map(function ($o) {
              return $o->string;
          })
          ->toArray();

        $this->assertSame(['bar', 'baz', 'foo'], $results);
    }

    public function testGroupWithJsonType(): void
    {
        $data = $this->generator
          ->clear()
          ->faker('attributes.date', 'randomElement', ['2020-01-01', '2021-01-01', '2022-01-01'])
          ->generate(50);

        $this->Objects->saveManyOrFail($this->Objects->newEntities($data));
        $data = (new Collection($data))->countBy('attributes.date')->toArray();

        $q = $this->Objects->find('all', ['jsonTypeMap' => ['attributes->date' => 'date']]);

        $results = $q
          ->select(['count' => $q->func()->count('*'), 'v' => 'attributes->date'])
          ->group('attributes->date')
          ->all();

        foreach ($results as $s) {
            $k = $s->v->format('Y-m-d');
            $this->assertEquals($data[$k], $s->count);
        }
    }

    public function testHaving(): void
    {
        $data = $this->generator
          ->clear()
          ->faker('attributes.i', 'randomElement', [0,1,2])
          ->faker('attributes.j', 'randomElement', [0,1,2])
          ->generate(50);

        $this->Objects->saveManyOrFail($this->Objects->newEntities($data));

        $q = $this->Objects->find();

        $results = $q
          ->select([
              'i' => 'attributes->i',
              'j' => 'attributes->j',
              'total' => 'attributes->i + attributes->j',
          ])
          ->having(['total >= ' => 3])
          ->all();

        foreach ($results as $o) {
            $this->assertEquals($o->i + $o->j, $o->total);
            $this->assertTrue($o->total >= 3);
        }
    }
}
