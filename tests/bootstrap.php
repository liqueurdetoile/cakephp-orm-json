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

// Build DSN for local/CI testing
switch (env('DB_FAMILY')) {
    case 'mysql':
        $dsn = 'mysql://root:root@127.0.01/cake_orm_json';
        $sniffer = MysqlTriggerBasedTableSniffer::class;
        break;
    default:
      // Fallback on local config. Should ne updated as needed
        $dsn = 'mysql://root@localhost/cakeormjson_test?log=false';
        $sniffer = MysqlTriggerBasedTableSniffer::class;
}

// Creates test connection
ConnectionManager::setConfig('test', [
  'url' => env('DB_URL', $dsn),
  'tableSniffer' => $sniffer,
]);

// Run migrations
$migrator = new Migrator();
$migrator->run([], false);
