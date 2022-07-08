<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\TestCase\DataGenerator;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class WhereRootTableTest extends TestCase
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

    public function testWhereOnMissingKey(): void
    {
        $q = $this->Objects->find()->where(['attributes->missing IS' => null]);
        $this->assertEquals(50, $q->count());

        $q = $this->Objects->find()->where(['attributes->missing IS NOT' => null]);
        $this->assertEquals(0, $q->count());
    }

    public function whereOnNullData(): array
    {
        return [
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
    public function testWhereOnNull($clauses, $expector): void
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

    /** @dataProvider whereOnBooleanData */
    public function testWhereOnBoolean($clauses, $expector): void
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
                return $o->{'attributes->number'} >= 2 && $o->{'attributes->number'} <= 3;
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
        ];
    }

    /** @dataProvider whereOnIntegerData */
    public function testWhereOnInteger($clauses, $expector): void
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

    /** @dataProvider whereOnDoubleData */
    public function testWhereOnDouble($clauses, $expector): void
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

    /** @dataProvider whereOnStringData */
    public function testWhereOnString($clauses, $expector): void
    {
        $q = $this->Objects->find()->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }

    public function whereOnDateData(): array
    {
        return [
          // [
          //   ['attributes->date' => '2020-06-01'],
          //   function ($o): bool {
          //       return $o->{'attributes->date'} === '2020-06-01';
          //   },
          // ],
          [
            ['attributes->date' => new FrozenDate('2020-06-01')],
            function ($o): bool {
                return $o->{'attributes->date'} === '2020-06-01';
            },
          ],
        ];
    }

    /** @dataProvider whereOnDateData */
    public function testWhereOnDate($clauses, $expector): void
    {
        $q = $this->Objects->find('all', ['jsonDateTimeTemplate' => 'Y-m-d'])->where($clauses);
        $objects = $q->all();
        $this->assertNotEmpty($objects->toArray());
        foreach ($objects as $object) {
            $this->assertTrue($expector($object));
        }
    }
}
