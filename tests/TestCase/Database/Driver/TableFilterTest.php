<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class TableFilterTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable
     */
    public $Objects;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var iterable<\Cake\Datasource\EntityInterface>
     */
    public $objects;

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
        $this->data = $this->generator
          ->clear()
          ->faker('attributes.mixed', 'randomElement', [null, true, false, 'bar', 'baz'])
          ->faker('attributes.boolean', 'randomElement', [true, false])
          ->faker('attributes.number', 'randomElement', [0,1,2,3,4,'2'])
          ->faker('attributes.float', 'randomElement', [0, 0.1, 0.2, 0.3, 0.4])
          ->faker('attributes.string', 'randomElement', ['foo', 'bar', 'baz', 'boo'])
          ->faker('attributes.date', 'randomElement', ['2019-06-01', '2020-06-01', '2020-07-01'])
          ->faker('attributes.array', 'randomElement', [['a'], ['b']])
          ->faker('attributes.object', 'randomElement', [['a' => 1], ['b' => 1]])
          ->faker('attributes.really.deep.boolean', 'randomElement', [true, false])
          ->faker('attributes.really.deep.number', 'randomElement', [0,1,2,3,4])
          ->faker('attributes.really.deep.float', 'randomElement', [0, 0.1, 0.2, 0.3, 0.4])
          ->faker('attributes.really.deep.string', 'randomElement', ['foo', 'bar', 'baz', 'boo'])
          ->faker('attributes.really.deep.date', 'randomElement', ['2019-06-01', '2020-06-01', '2020-07-01'])
          ->generate(50);

        $objects = $this->Objects->newEntities($this->data);
        $this->objects = $this->Objects->saveMany($objects);
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

    public function testWhereOnMissingKey(): void
    {
        $q = $this->Objects->find()->where(['attributes->missing IS' => null]);
        $this->assertEquals(50, $q->count());

        $q = $this->Objects->find()->where(['attributes->missing IS NOT' => null]);
        $this->assertEquals(0, $q->count());

        // Ignore missing key option
        $q = $this->Objects
          ->find('all', ['ignoreMissingPath' => true])
          ->where(['attributes->missing IS' => null]);
        $this->assertEquals(0, $q->count());
    }

    public function whereOnNullData(): array
    {
        return [
          [
            ['at2 IS' => null],
            function ($o): bool {
                return $o->at2 === null;
            },
          ],
          [
            ['attributes->mixed IS' => null],
            function ($o): bool {
                return $o->{'attributes->mixed'} === null;
            },
          ],
          [
            ['attributes->mixed IS NOT' => null],
            function ($o): bool {
                return $o->{'attributes->mixed'} !== null;
            },
          ],
        ];
    }

    /** @dataProvider whereOnNullData */
    public function testWhereOnNull(array $clauses, callable $expector): void
    {
        $q = $this->Objects->find()->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }

    public function whereOnBooleanData(): array
    {
        return [
          [
            ['attributes->boolean' => true],
            function ($o): bool {
                return $o->{'attributes->boolean'} === true;
            },
          ],
          [
            function ($q) {
                return $q->eq('attributes->boolean', true);
            },
            function ($o): bool {
                return $o->{'attributes->boolean'} === true;
            },
          ],
          [
            ['attributes->boolean <>' => true],
            function ($o): bool {
                return $o->{'attributes->boolean'} === false;
            },
          ],
          [
            ['attributes->really.deep.boolean' => true],
            function ($o): bool {
                return $o->{'attributes->really.deep.boolean'} === true;
            },
          ],
          [
            function ($q) {
                return $q->notEq('attributes->boolean', true);
            },
            function ($o): bool {
                return $o->{'attributes->boolean'} === false;
            },
          ],
          [
            ['not' => ['attributes->boolean' => true]],
            function ($o): bool {
                return $o->{'attributes->boolean'} === false;
            },
          ],
        ];
    }

    /**
     * @dataProvider whereOnBooleanData
     * @param array|\Closure    $clauses   [description]
     * @param callable $expector  [description]
     */
    public function testWhereOnBoolean($clauses, callable $expector): void
    {
        $q = $this->Objects->find()->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }

    public function whereOnIntegerData(): array
    {
        return [
          [
            ['attributes->number' => 2],
            function ($o): bool {
                return $o->{'attributes->number'} === 2;
            },
          ],
          [
            ['attributes->number <=' => 2],
            function ($o): bool {
                return $o->{'attributes->number'} <= 2;
            },
          ],
          [
            ['attributes->number <' => 2],
            function ($o): bool {
                return $o->{'attributes->number'} < 2;
            },
          ],
          [
            ['attributes->number <>' => 2],
            function ($o): bool {
                return $o->{'attributes->number'} !== 2;
            },
          ],
          [
            ['attributes->number !=' => 2],
            function ($o): bool {
                return $o->{'attributes->number'} !== 2;
            },
          ],
          [
            function ($q) {
                return $q->notEq('attributes->number', 2);
            },
            function ($o): bool {
                return $o->{'attributes->number'} !== 2;
            },
          ],
          [
            function ($q) {
                return $q->gte('attributes->number', 2);
            },
            function ($o): bool {
                return $o->{'attributes->number'} >= 2;
            },
          ],
          [
            function ($q) {
                return $q->between('attributes->number', 2, 3);
            },
            function ($o): bool {
                return is_int($o->{'attributes->number'}) && $o->{'attributes->number'} >= 2 && $o->{'attributes->number'} <= 3;
            },
          ],
          [
            function ($exp, $q) {
                return $exp->notEq('attributes->number', $q->newExpr('1 + 1'));
            },
            function ($o): bool {
                return $o->{'attributes->number'} !== 2;
            },
          ],
          [
            function ($exp, $q) {
                $sq = $q->getRepository()->find()->select('id')->where(['attributes->number' => 2]);

                return $exp->in('id', $sq);
            },
            function ($o): bool {
                return $o->{'attributes->number'} === 2;
            },
          ],
          [
            ['attributes->number' => '2'],
            function ($o): bool {
                return $o->{'attributes->number'} === '2';
            },
          ],
        ];
    }

    /**
     * @dataProvider whereOnIntegerData
     * @param array|\Closure    $clauses   [description]
     * @param callable $expector  [description]
     */
    public function testWhereOnInteger($clauses, callable $expector): void
    {
        $q = $this->Objects->find()->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }

    public function whereOnDoubleData(): array
    {
        return [
          [
            ['attributes->float' => 0.1],
            function ($o): bool {
                return $o->{'attributes->float'} === 0.1;
            },
          ],
          [
            ['attributes->really.deep.float' => 0.1],
            function ($o): bool {
                return $o->{'attributes->really.deep.float'} === 0.1;
            },
          ],
          [
            ['attributes->float >' => 0.1],
            function ($o): bool {
                return $o->{'attributes->float'} > 0.1;
            },
          ],
          [
            function ($q) {
                return $q->gt('attributes->float', 0.1);
            },
            function ($o): bool {
                return $o->{'attributes->float'} > 0.1;
            },
          ],
          [
            function ($q) {
                return $q->between('attributes->float', 0.2, 0.3);
            },
            function ($o): bool {
                return $o->{'attributes->float'} >= 0.2 && $o->{'attributes->float'} <= 0.3;
            },
          ],
        ];
    }

    /**
     * @dataProvider whereOnDoubleData
     * @param array|\Closure    $clauses   [description]
     * @param callable $expector  [description]
     */
    public function testWhereOnDouble($clauses, callable $expector): void
    {
        $q = $this->Objects->find()->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }

    public function whereOnStringData(): array
    {
        return [
          [
            ['attributes->string' => 'foo'],
            function ($o): bool {
                return $o->{'attributes->string'} === 'foo';
            },
          ],
          [
            ['attributes->string <>' => 'foo'],
            function ($o): bool {
                return $o->{'attributes->string'} !== 'foo';
            },
          ],
          [
            ['attributes->string !=' => 'foo'],
            function ($o): bool {
                return $o->{'attributes->string'} !== 'foo';
            },
          ],
          [
            function ($exp) {
                return $exp->notEq('attributes->string', 'foo');
            },
            function ($o): bool {
                return $o->{'attributes->string'} !== 'foo';
            },
          ],
          [
            ['attributes->string LIKE' => 'ba%'],
            function ($o): bool {
                return strpos($o->{'attributes->string'}, 'ba') === 0;
            },
          ],
          [
            function ($exp) {
                return $exp->like('attributes->string', 'ba%');
            },
            function ($o): bool {
                return strpos($o->{'attributes->string'}, 'ba') === 0;
            },
          ],
          [
            ['attributes->string NOT LIKE' => '%oo'],
            function ($o): bool {
                return strpos($o->{'attributes->string'}, 'ba') === 0;
            },
          ],
          [
            function ($exp) {
                return $exp->notLike('attributes->string', '%oo');
            },
            function ($o): bool {
                return strpos($o->{'attributes->string'}, 'ba') === 0;
            },
          ],
          [
            ['attributes->string IN' => ['bar', 'baz']],
            function ($o): bool {
                return strpos($o->{'attributes->string'}, 'ba') === 0;
            },
          ],
          [
            function ($exp) {
                return $exp->in('attributes->string', ['bar', 'baz']);
            },
            function ($o): bool {
                return strpos($o->{'attributes->string'}, 'ba') === 0;
            },
          ],
        ];
    }

    /**
     * @dataProvider whereOnStringData
     * @param array|\Closure    $clauses   [description]
     * @param callable $expector  [description]
     */
    public function testWhereOnString($clauses, callable $expector): void
    {
        $q = $this->Objects->find()->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }

    public function testWhereOnArray(): void
    {
        $q = $this->Objects->find()->where(['attributes->array' => ['a']]);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertSame(['a'], $object->{'attributes->array'});
        }
    }

    public function testWhereOnObject(): void
    {
        $q = $this->Objects->find()->where(['attributes->object' => ['a' => 1]]);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertSame(['a' => 1], $object->{'attributes->object'});
        }
    }

    public function whereOnDateData(): array
    {
        return [
          [
            ['attributes->date' => '2020-06-01'],
            function ($o): bool {
                return $o->{'attributes->date'} === '2020-06-01';
            },
          ],
          [
            ['attributes->date' => new FrozenDate('2020-06-01')],
            function ($o): bool {
                return $o->{'attributes->date'} instanceof FrozenDate &&
                  $o->{'attributes->date'}->i18nFormat('yyyy-MM-dd') === '2020-06-01';
            },
            ['attributes->date' => 'date'],
          ],
        ];
    }

    /** @dataProvider whereOnDateData */
    public function testWhereOnDate(array $clauses, callable $expector, ?array $types = null): void
    {
        $q = $this->Objects->find('all', ['jsonTypeMap' => $types])->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }
}
