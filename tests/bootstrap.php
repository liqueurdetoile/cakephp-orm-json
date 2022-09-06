<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;
use CakephpTestSuiteLight\Sniffer\MysqlTriggerBasedTableSniffer;
use Migrations\TestSuite\Migrator;

/**
 * Test suite bootstrap for CakephpOrmJson plugin
 * We handle here the configuration to support matrix between :
 * - Cakephp versions : 3.5, 4.1, latest
 * - Database engines : Mysql, SQLite, ...
 */
include_once 'config/common.php';

// Fallback dsn for local testing
// Just update to match your testing database configuration
$dsn = 'mysql://root@localhost/cakeormjson_test?log=false';

// Creates test connection
ConnectionManager::setConfig('test', [
  'url' => env('DB_URL', $dsn),
  'tableSniffer' => MysqlTriggerBasedTableSniffer::class,
]);

// Run migrations
$migrator = new Migrator();
$migrator->run([], false);
