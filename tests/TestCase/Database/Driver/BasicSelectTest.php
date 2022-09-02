<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\I18n\FrozenTime;

/**
 * In Basic test, we're not using advanced ORM schema reflection ability
 * Therefore, all typings and aliases have to be handled manually at each requests
 */
class BasicSelectTest extends TestCase
{
    /**
     * Fixture data
     *
     * @var array
     */
    public $row;

    /**
     * Stores regular and JSON types
     *
     * @var array
     */
    public $types;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->types = [
          'id' => 'uuid',
          'attributes' => 'json',
          'attributes->datetime' => 'datetime',
          'attributes->callback' => [
            'type' => 'datetime', // Should be ignored when calling data type toPHP and toDatabase in favor of callbacks
            'toPHP' => function ($year) {
                return FrozenTime::createFromFormat('Y', $year);
            },
            'toDatabase' => function ($time) {
                return (string)($time->year - 2);
            },
          ],
        ];

        // Let's insert a row whith each data type
        $this->row = $this->generator
          ->clear()
          ->static('id', 'b8b04734-5ea1-4220-9798-58edef3ffcd7')
          ->static('attributes.null', null)
          ->static('attributes.boolean', true)
          ->static('attributes.int', 10)
          ->static('attributes.double', 125.25)
          ->static('attributes.scientific', 2.1E-5)
          ->static('attributes.string', 'test')
          ->static('attributes.datetime', new FrozenTime('2021-01-31 22:11:30'))
          ->static('attributes.callback', new FrozenTime('2021-01-31 22:11:30'))
          ->static('attributes.array', ['a'])
          ->static('attributes.object', ['a' => 'a'])
          ->static('attributes.arrayobject', [['a' => 'a'], ['a' => 'b']])
          ->generate(1);

        $this->connection->insert('objects', $this->row, $this->types);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testFetch(): void
    {
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes($this->types);

        $results = $query
          ->setSelectTypeMap($map)
          ->select('*')
          ->from('objects')
          ->execute()
          ->fetchAll('assoc');

        $this->assertEquals(null, $results[0]['attributes']['null']);
        $this->assertEquals(true, $results[0]['attributes']['boolean']);
        $this->assertEquals(10, $results[0]['attributes']['int']);
        $this->assertEquals(125.25, $results[0]['attributes']['double']);
        $this->assertEquals(2.1E-5, $results[0]['attributes']['scientific']);
        $this->assertInstanceOf(FrozenTime::class, $results[0]['attributes']['datetime']);
        $this->assertEquals('2021-01-31 22:11:30', $results[0]['attributes']['datetime']->i18nFormat('yyyy-MM-dd HH:mm:ss'));
        $this->assertInstanceOf(FrozenTime::class, $results[0]['attributes']['callback']);
        $this->assertEquals(2019, $results[0]['attributes']['callback']->year);
        $this->assertSame(['a'], $results[0]['attributes']['array']);
        $this->assertSame(['a' => 'a'], $results[0]['attributes']['object']);
        $this->assertSame([['a' => 'a'], ['a' => 'b']], $results[0]['attributes']['arrayobject']);
    }

    public function testFetchWithAliases(): void
    {
        $query = $this->connection->newQuery();
        $map = $query->getSelectTypeMap();
        $map->setTypes(['id' => 'uuid', 'attributes->datetime' => 'datetime', 'attributes->boolean' => 'boolean']);

        $results = $query
          ->setSelectTypeMap($map)
          ->select(['id', 'attributes->boolean', 'datetime' => 'attributes->datetime'])
          ->from('objects')
          ->execute()
          ->fetchAll('assoc');

        $this->assertEquals('b8b04734-5ea1-4220-9798-58edef3ffcd7', $results[0]['id']);
        $this->assertEquals(true, $results[0]['attributes_boolean']);
        $this->assertInstanceOf(FrozenTime::class, $results[0]['datetime']);
        $this->assertEquals('2021-01-31 22:11:30', $results[0]['datetime']->i18nFormat('yyyy-MM-dd HH:mm:ss'));
    }
}
