<?php
declare(strict_types=1);

use Migrations\TestSuite\Migrator;

/**
 * Test suite bootstrap for PluginTemplate.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */
$dsn = env('TRAVIS', false) ?
  'mysql://root:root@localhost/cakeormjson_test' :
  'mysql://root@localhost/cakeormjson_test';

putenv('DB_URL=' . $dsn);
putenv('TESTING=1');

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};

$root = $findRoot(__FILE__);
unset($findRoot);

if (file_exists($root . '/config/bootstrap.php')) {
    include $root . '/config/bootstrap.php';
} else {
    include $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';
}

// Run migrations
$migrator = new Migrator();
$migrator->run([], false);
