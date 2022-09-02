<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class BasicFilterTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    public $types = [
      'id' => 'uuid',
      'attributes' => 'json',
      'at2' => 'json',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $data = $this->generator
          ->clear()
          ->faker('id', 'uuid')
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

        foreach ($data as $row) {
            $this->connection->insert('objects', $row, $this->types);
        }
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
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where(['attributes->missing IS' => null])
          ->execute()
          ->fetchAll('assoc');

        $this->assertEquals(50, count($results));

        $results = $this->connection->newQuery()
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where(['attributes->missing IS NOT' => null])
          ->execute()
          ->fetchAll('assoc');

        $this->assertEquals(0, count($results));
    }

    public function whereOnNullData(): array
    {
        return [
          [
            ['at2 IS' => null],
            function ($o): bool {
                return $o['at2'] === null;
            },
          ],
          [
            ['attributes->mixed IS' => null],
            function ($o): bool {
                return $o['attributes']['mixed'] === null;
            },
          ],
          [
            ['attributes->mixed IS NOT' => null],
            function ($o): bool {
                return $o['attributes']['mixed'] !== null;
            },
          ],
        ];
    }

    /** @dataProvider whereOnNullData */
    public function testWhereOnNull($clauses, $expector): void
    {
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where($clauses)
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $this->assertTrue($expector($row));
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
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where($clauses)
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $object = new DatFieldEntity($row);

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
                $sq = $q->getConnection()->newQuery();
                $map = $sq->getSelectTypeMap();
                $map->setTypes($this->types);

                $sq = $sq
                  ->setSelectTypeMap($map)
                  ->select('id')
                  ->from('objects')
                  ->where(['attributes->number' => 2]);

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

    /** @dataProvider whereOnIntegerData */
    public function testWhereOnInteger($clauses, $expector): void
    {
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where($clauses)
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $object = new DatFieldEntity($row);

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
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where($clauses)
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $object = new DatFieldEntity($row);

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
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where($clauses)
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $object = new DatFieldEntity($row);

            $this->assertTrue($expector($object));
        }
    }

    public function testWhereOnArray(): void
    {
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where(['attributes->array' => ['a']])
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $object = new DatFieldEntity($row);

            $this->assertSame(['a'], $object->{'attributes->array'});
        }
    }

    public function testWhereOnObject(): void
    {
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where(['attributes->object' => ['a' => 1]])
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $object = new DatFieldEntity($row);

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
    public function testWhereOnDate($clauses, $expector, $types = []): void
    {
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types + $types);

        $results = $query
          ->setTypeMap($this->types + $types)
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->where($clauses)
          ->execute()
          ->fetchAll('assoc');

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $object = new DatFieldEntity($row);

            $this->assertTrue($expector($object));
        }
    }
}
