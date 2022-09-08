<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\I18n\FrozenTime;

/**
 * In Basic test, we're not using advanced ORM schema reflection ability
 * Therefore, all typings and aliases have to be handled manually at each requests
 */
class BasicInsertAndUpdateTest extends TestCase
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
          'attributes->year' => [
            'type' => 'datetime', // Should be ignored when calling data type toPHP and toDatabase in favor of callbacks
            'toPHP' => function ($year) {
                return FrozenTime::createFromFormat('Y', (string)$year);
            },
            'toDatabase' => function ($time) {
                return $time->year;
            },
          ],
          'attributes->relativeYear' => [
            'toPHP' => function ($year) {
                return FrozenTime::createFromFormat('Y', (string)$year);
            },
            'toDatabase' => function ($whatever, $data) {
                $data = $data['attributes']['datetime'];
                $time = is_string($data) ? FrozenTime::createFromFormat('Y-m-d H:i:s', $data) : $data;

                return $time->year;
            },
          ],
        ];
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

    public function testInsertAndUpdate(): void
    {
        $data = $this->generator
          ->clear()
          ->static('id', 'b8b04734-5ea1-4220-9798-58edef3ffcd7')
          ->static('attributes.null', null)
          ->static('attributes.boolean', true)
          ->static('attributes.int', 10)
          ->static('attributes.double', 125.25)
          ->static('attributes.scientific', 2.1E-5)
          ->static('attributes.string', 'test')
          ->static('attributes.datetime', new FrozenTime('2021-01-31 22:11:30'))
          ->static('attributes.year', new FrozenTime('2021-01-31 22:11:30'))
          ->static('attributes.relativeYear', null)
          ->static('attributes.array', ['a'])
          ->static('attributes.object', ['a' => 'a'])
          ->static('attributes.arrayobject', [['a' => 'a'], ['a' => 'b']])
          ->generate(1);

        $this->connection->insert('objects', $data, $this->types);

        $row = $this->connection->execute('SELECT * FROM objects')->fetchAll('assoc');
        $this->assertNotFalse($row);
        $attributes = json_decode($row[0]['attributes'], true);

        $this->assertEquals(null, $attributes['null']);
        $this->assertEquals(true, $attributes['boolean']);
        $this->assertEquals(10, $attributes['int']);
        $this->assertEquals(125.25, $attributes['double']);
        $this->assertEquals(2.1E-5, $attributes['scientific']);
        $this->assertEquals('2021-01-31 22:11:30', $attributes['datetime']);
        $this->assertEquals(2021, $attributes['year']);
        $this->assertEquals(2021, $attributes['relativeYear']);
        $this->assertSame(['a'], $attributes['array']);
        $this->assertSame(['a' => 'a'], $attributes['object']);
        $this->assertSame([['a' => 'a'], ['a' => 'b']], $attributes['arrayobject']);

        $data['attributes']['datetime'] = $data['attributes']['datetime']->year(2022);

        $this->connection->update(
            'objects',
            ['attributes' => $data['attributes'], 'at2' => 'test'],
            ['id' => $data['id']],
            $this->types + ['at2' => 'json']
        );

        $row = $this->connection->execute('SELECT * FROM objects')->fetchAll('assoc');
        $this->assertNotFalse($row);
        $attributes = json_decode($row[0]['attributes'], true);
        $at2 = json_decode($row[0]['at2'], true);

        $this->assertEquals(null, $attributes['null']);
        $this->assertEquals(true, $attributes['boolean']);
        $this->assertEquals(10, $attributes['int']);
        $this->assertEquals(125.25, $attributes['double']);
        $this->assertEquals(2.1E-5, $attributes['scientific']);
        $this->assertEquals('2022-01-31 22:11:30', $attributes['datetime']);
        $this->assertEquals(2021, $attributes['year']);
        $this->assertEquals(2022, $attributes['relativeYear']);
        $this->assertSame(['a'], $attributes['array']);
        $this->assertSame(['a' => 'a'], $attributes['object']);
        $this->assertSame([['a' => 'a'], ['a' => 'b']], $attributes['arrayobject']);
        $this->assertEquals('test', $at2);
    }
}
