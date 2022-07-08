<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\Collection\Collection;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\TestCase\DataGenerator;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class OrderRootTableTest extends TestCase
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
          ->faker('attributes.number', 'randomNumber')
          ->faker('attributes.float', 'randomFloat', 2, 0.01, 0.05)
          ->faker('attributes.string', 'name')
          ->faker('attributes.date', 'date', 'Y-m-d H:i:s')
          ->faker('attributes.really.deep.number', 'randomNumber')
          ->faker('attributes.really.deep.float', 'randomFloat', 2, 0.01, 0.05)
          ->faker('attributes.really.deep.string', 'name')
          ->faker('attributes.really.deep.date', 'date', 'Y-m-d H:i:s')
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

    public function dataOrder(): array
    {
        return [
          ['number@attributes', 'attributes.number', SORT_ASC],
          [['number@attributes' => 'DESC'], 'attributes.number', SORT_DESC],
          ['float@attributes', 'attributes.float', SORT_ASC],
          [['float@attributes' => 'DESC'], 'attributes.float', SORT_DESC],
          ['string@attributes', 'attributes.string', SORT_ASC],
          [['string@attributes' => 'DESC'], 'attributes.string', SORT_DESC],
          ['date@attributes', 'attributes.date', SORT_ASC],
          [['date@attributes' => 'DESC'], 'attributes.date', SORT_DESC],
          ['really.deep.number@attributes', 'attributes.really.deep.number', SORT_ASC],
          [['really.deep.number@attributes' => 'DESC'], 'attributes.really.deep.number', SORT_DESC],
          ['really.deep.float@attributes', 'attributes.really.deep.float', SORT_ASC],
          [['really.deep.float@attributes' => 'DESC'], 'attributes.really.deep.float', SORT_DESC],
          ['really.deep.string@attributes', 'attributes.really.deep.string', SORT_ASC],
          [['really.deep.string@attributes' => 'DESC'], 'attributes.really.deep.string', SORT_DESC],
          ['really.deep.date@attributes', 'attributes.really.deep.date', SORT_ASC],
          [['really.deep.date@attributes' => 'DESC'], 'attributes.really.deep.date', SORT_DESC],
        ];
    }

    /**
     * @dataProvider dataOrder
     */
    public function testOrder($order, $field, $sort): void
    {
        $expected = (new Collection($this->data))->sortBy($field, $sort, SORT_NATURAL)->extract($field)->toArray(false);
        $objects = $this->Objects->find()->order($order)->all()->extract($field)->toArray(false);

        $this->assertSame($expected, $objects);
    }

    public function testMultipleOrder(): void
    {
        $objects = $this->Objects->find()->order(['float@attributes', 'number@attributes' => 'DESC'])->all();
        $previous = null;

        foreach ($objects as $o) {
            if ($previous) {
                $this->assertTrue(
                    $o->{'float@attributes'} > $previous->{'float@attributes'}
                    || $o->{'number@attributes'} <= $previous->{'number@attributes'}
                );
            }

            $previous = $o;
        }
    }
}
