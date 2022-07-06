<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class DatabaseSetup extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('objects', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('attributes', 'json', ['null' => true]);
        $table->addColumn('at2', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('agents', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('attributes', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('clients', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('attributes', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('orders', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('attributes', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('orders_products', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('attributes', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('products', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('attributes', 'json', ['null' => true]);
        $table->create();
    }
}
