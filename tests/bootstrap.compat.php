<?php
declare(strict_types=1);

use Cake\Database\Type;
use Cake\Datasource\ConnectionManager;
use CakephpTestSuiteLight\Sniffer\MysqlTriggerBasedTableSniffer;

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
        $dsn = 'mysql://root:root@127.0.01/cakephp_orm_json';
        $sniffer = MysqlTriggerBasedTableSniffer::class;
        break;
    default:
        // Fallback on local config. Should be updated as needed
        $dsn = 'mysql://root@localhost/cakeormjson_test?log=false';
        $sniffer = MysqlTriggerBasedTableSniffer::class;
}

// Creates test connection
ConnectionManager::setConfig('test', [
  'url' => env('DB_URL', $dsn),
  'tableSniffer' => $sniffer,
]);

define('COMPAT_MODE', true);

// Swap to immutable for types to avoid instanceof FrozenXX failure in tests
Type::build('datetime')->useImmutable(); // @phpstan-ignore-line
Type::build('date')->useImmutable(); // @phpstan-ignore-line
Type::build('time')->useImmutable(); // @phpstan-ignore-line
Type::build('timestamp')->useImmutable(); // @phpstan-ignore-line

// Stub modern truncating trait
require_once 'StubTruncateDirtyTables.php';

// Enable compatibility mode
\Lqdt\OrmJson\DatField\Compat3x::enable();

// Migrate test database
\CakephpTestMigrator\Migrator::migrate([], ['truncate' => false]); // @phpstan-ignore-line
