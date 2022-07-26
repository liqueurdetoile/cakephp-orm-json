<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\ORM;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Database\Driver\DatFieldMysql;
use Lqdt\OrmJson\Model\Behavior\DatFieldBehavior;
use Lqdt\OrmJson\Test\Model\Table\DatfieldsTable;

class DatFieldAwareTraitTest extends TestCase
{
    /**
     * Checks that useDatFields operates in initialize hook of Table
     */
    public function testUpgradeAndRevertWithTrait(): void
    {
        $table = TableRegistry::get('Table', ['className' => DatfieldsTable::class]);
        $connection = $table->getConnection();

        $this->assertEquals('test', $connection->configName());
        $this->assertNotInstanceOf(DatFieldMysql::class, $connection->getDriver());

        // Permanently upgrade connection for this instance
        $connection = $table->useDatFields()->getConnection();

        $this->assertEquals('test_dfm', $connection->configName());
        $this->assertInstanceOf(DatFieldMysql::class, $connection->getDriver());

        // Permanently downgrade connection for this instance
        $connection = $table->useDatFields(false)->getConnection();

        $this->assertEquals('test', $connection->configName());
        $this->assertNotInstanceOf(DatFieldMysql::class, $connection->getDriver());

        // Upgrade connection only or this query
        $q = $table->find('datfields');
        $connection = $table->getConnection();
        $queryConnection = $q->getConnection();

        $this->assertEquals('test', $connection->configName());
        $this->assertNotInstanceOf(DatFieldMysql::class, $connection->getDriver());
        $this->assertEquals('test_dfm', $queryConnection->configName());
        $this->assertInstanceOf(DatFieldMysql::class, $queryConnection->getDriver());

        TableRegistry::clear();
    }

    /**
     * Checks autoupgrade with behavior
     */
    public function testUseDatFieldsWhithBehavior(): void
    {
        $table = TableRegistry::get('Objects', ['className' => Table::class]);
        $table->addBehavior(DatFieldBehavior::class);
        $connection = $table->getConnection();

        $this->assertEquals('test', $connection->configName());
        $this->assertNotInstanceOf(DatFieldMysql::class, $connection->getDriver());

        $table->useDatFields();
        $connection = $table->getConnection();

        $this->assertEquals('test_dfm', $connection->configName());
        $this->assertInstanceOf(DatFieldMysql::class, $connection->getDriver());

        TableRegistry::clear();
    }

    /**
     * Checks autoupgrade with behavior
     */
    public function testFinderWithBehavior(): void
    {
        $table = TableRegistry::get('Objects', ['className' => Table::class]);
        $connection = $table->getConnection();

        $this->assertEquals('test', $connection->configName());
        $this->assertNotInstanceOf(DatFieldMysql::class, $connection->getDriver());

        $table->addBehavior(DatFieldBehavior::class, ['upgrade' => true]);
        $connection = $table->getConnection();

        $this->assertEquals('test_dfm', $connection->configName());
        $this->assertInstanceOf(DatFieldMysql::class, $connection->getDriver());

        TableRegistry::clear();
    }

    /**
     * Checks autoupgrade with behavior
     */
    public function testAutoUpgradeWhenUsingBehavior(): void
    {
        $table = TableRegistry::get('Objects', ['className' => Table::class]);
        $connection = $table->getConnection();

        $this->assertEquals('test', $connection->configName());
        $this->assertNotInstanceOf(DatFieldMysql::class, $connection->getDriver());

        $table->addBehavior(DatFieldBehavior::class, ['upgrade' => true]);
        $connection = $table->getConnection();

        $this->assertEquals('test_dfm', $connection->configName());
        $this->assertInstanceOf(DatFieldMysql::class, $connection->getDriver());

        TableRegistry::clear();
    }
}
