<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase as RootTestCase;
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
     * @var \Cake\Database\Connection
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

        $trait = new \Lqdt\OrmJson\Test\Model\DatFieldAware();
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('test');
        $this->connection = $trait->getUpgradedConnectionForDatFields($connection);
        $this->generator = new DataGenerator();
    }
}
