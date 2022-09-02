<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase as RootTestCase;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;
use Lqdt\OrmJson\Test\Fixture\DataGenerator;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class TestCase extends RootTestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * Test connection
     *
     * @var \Cake\Datasource\ConnectionInterface
     */
    public $connection;

    /**
     * Data Generator
     *
     * @var \Lqdt\OrmJson\Test\Fixture\DataGenerator
     */
    public $generator;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $trait = $this->getObjectForTrait(DatFieldAwareTrait::class);
        $this->connection = $trait->getUpgradedConnectionForDatFields(ConnectionManager::get('test'));
        $this->generator = new DataGenerator();
    }
}
