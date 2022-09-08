<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\ORM\TableRegistry;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class TableFunctionsTest extends TestCase
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

    public function testCount(): void
    {
        $data = $this->generator
          ->clear()
          ->static('attributes.item', 'test')
          ->generate(10);

        $this->Objects->saveMany($this->Objects->newEntities($data));

        $q = $this->Objects->find();
        $q->select(['count' => $q->func()->count('attributes->item')]);

        /** @phpstan-ignore-next-line */
        $this->assertEquals(10, $q->first()->count);
    }

    public function testSum(): void
    {
        $data = $this->generator
          ->clear()
          ->faker('attributes.n', 'randomElement', [1, '1'])
          ->generate(10);

        $this->Objects->saveMany($this->Objects->newEntities($data));

        $q = $this->Objects->find();
        $q->select(['total' => $q->func()->sum('attributes->n')]);

        /** @phpstan-ignore-next-line */
        $this->assertEquals(10, $q->first()->total);
    }
}
