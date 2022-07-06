<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class SelectRootTableTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    public $Objects;
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\ObjectsFixture'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable']);
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

    public function selectData()
    {
        return [
          [
            ['id', 'string@attributes'],
            [
              'id' => '993ce30f-01dd-46e9-ad75-4fc104e42c70',
              'attributes_string' => 'string1',
            ],
          ],
          [
            ['id', 'string' => 'string@attributes'],
            [
              'id' => '993ce30f-01dd-46e9-ad75-4fc104e42c70',
              'string' => 'string1',
            ],
          ],
          [
            ['id', 'dat.string' => 'string@attributes'],
            [
              'id' => '993ce30f-01dd-46e9-ad75-4fc104e42c70',
              'dat.string' => 'string1',
            ],
          ],
          [
            ['string@attributes'],
            [
              'attributes+objectsstring' => 'string1',
            ],
            [
              'jsonSeparator' => '+',
              'jsonPropertyTemplate' => '{{field}}{{separator}}{{model}}{{path}}',
            ],
          ],
          [
            ['id', 'string@attributes'],
            [
              'id' => '993ce30f-01dd-46e9-ad75-4fc104e42c70',
              'attributes' => [
                'string' => 'string1',
              ],
            ],
            [
              'keepJsonNested' => true,
            ],
          ],
          [
            ['string@attributes', 'decimal@attributes', 'deep@attributes'],
            [
              'attributes' => [
                'string' => 'string1',
                'decimal' => (float)1.2,
                'deep' => ['key' => 'deepkey1'],
              ],
            ],
            [
              'keepJsonNested' => true,
            ],
          ],
          [
            ['my.key' => 'deep.key@Objects.attributes'],
            ['my.key' => 'deepkey1'],
          ],
          [
            ['my.key' => 'deep.key@Objects.attributes'],
            ['my' => ['key' => 'deepkey1']],
            [
              'keepJsonNested' => true,
            ],
          ],
        ];
    }

    /**
     * @dataProvider selectData
     */
    public function testSelect($select, $expected, ?array $config = null)
    {
        if (!is_null($config)) {
            $this->Objects->configureJsonFields($config);
        }

        $result = $this->Objects->find()->select($select)->first();
        $this->assertEquals($expected, $result->toArray());
    }
}
