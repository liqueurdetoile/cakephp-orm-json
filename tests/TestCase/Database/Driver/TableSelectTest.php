<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class TableSelectTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable
     */
    public $Objects;

    /**
     * @var array
     */
    public $row = [];

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

        /** @phpstan-ignore-next-line */
        $this->connection->insert('objects', $this->row, [
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
        ]);
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

    public function testFetchWithJsonTypeMapOption(): void
    {
        $query = $this->Objects->find('all', [
          'jsonTypeMap' => [
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
          ],
        ]);
        $results = $query->toArray();

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

    public function testFetchWithJsonTypeMapOnTable(): void
    {
        $this->Objects->getSchema()->setJsonTypes([
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
        ]);

        $query = $this->Objects->find();
        $results = $query->toArray();

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
        // No need to declare type for attributes->boolean as it will parsed with json data type if nothing is specified
        $this->Objects->getSchema()->setJsonTypes([
          'attributes->datetime' => 'datetime',
        ]);

        $query = $this->Objects->find()->select(['id', 'attributes->boolean', 'datetime' => 'attributes->datetime']);
        $results = $query->toArray();

        $this->assertEquals('b8b04734-5ea1-4220-9798-58edef3ffcd7', $results[0]['id']);
        $this->assertEquals(true, $results[0]['attributes_boolean']);
        $this->assertInstanceOf(FrozenTime::class, $results[0]['datetime']);
        $this->assertEquals('2021-01-31 22:11:30', $results[0]['datetime']->i18nFormat('yyyy-MM-dd HH:mm:ss'));
    }
}
