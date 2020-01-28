<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class driverSelectTest extends TestCase
{
    public $Objects;
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\ObjectsFixture'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Objects);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function selectData()
    {
        return [
          [
            ['id', 'string@attributes', 'decimal@attributes', 'deep@attributes'],
            [
              'id' => 1,
              'attributes' => [
                'string' => 'string1',
                'decimal' => (float) 1.2,
                'deep' => ['key' => 'deepkey1']
              ]
            ]
          ],

          [
            ['string@attributes', 'decimal@attributes', 'deep@attributes'],
            [
              'attributes' => [
                'string' => 'string1',
                'decimal' => (float) 1.2,
                'deep' => ['key' => 'deepkey1']
              ]
            ]
          ],

          [
            ['string@attributes'],
            [
              'objects_attributes_string' => 'string1'
            ],
            true
          ],

          [
            ['string@attributes'],
            [
              'objects.attributes.string' => 'string1'
            ],
            true,
            '.'
          ],

          [
            ['string@attributes'],
            ['objects' => ['attributes' => ['string' => 'string1']]],
            true,
            false
          ],

          [
            ['id', 'string' => 'string@attributes'],
            ['id' => 1, 'string' => 'string1'],
            true,
            false
          ],

          [
            ['my.key' => 'deep.key@Objects.attributes'],
            ['my.key' => 'deepkey1'],
            true
          ],

          [
            ['my.key' => 'deep.key@Objects.attributes'],
            ['my' => ['key' => 'deepkey1']],
            true,
            false
          ],
        ];
    }

    /** @dataProvider selectData */
    public function testSelectAsArray($select, $expected, $extractOnSelect = null, $separator = null, $template = null)
    {
        if (!is_null($extractOnSelect)) {
            $this->Objects->setExtractOnSelect($extractOnSelect);
        }

        if (!is_null($separator)) {
            $this->Objects->setExtractAliasSeparator($separator);
        }

        if (!is_null($template)) {
            $this->Objects->setExtractAliasTemplate($template);
        }

        $result = $this->Objects->find()->select($select)->enableHydration(false)->first();
        $this->assertEquals($expected, $result);
    }

    /** @dataProvider selectData */
    public function testSelectAsEntity($select, $expected, $extractOnSelect = null, $separator = null, $template = null)
    {
        if (!is_null($extractOnSelect)) {
            $this->Objects->setExtractOnSelect($extractOnSelect);
        }

        if (!is_null($separator)) {
            $this->Objects->setExtractAliasSeparator($separator);
        }

        if (!is_null($template)) {
            $this->Objects->setExtractAliasTemplate($template);
        }

        $result = $this->Objects->find()->select($select)->first();
        $this->assertEquals($expected, $result->toArray());
    }
}
