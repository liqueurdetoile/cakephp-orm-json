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

        $table = $this->table('owners', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('data', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('buyers', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('data', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('things', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('owner_id', 'uuid');
        $table->addColumn('data', 'json', ['null' => true]);
        $table->create();
        $table->addForeignKey('owner_id', 'owners', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        $table->update();

        $table = $this->table('offers', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('buyer_id', 'uuid');
        $table->addColumn('thing_id', 'uuid');
        $table->addColumn('data', 'json', ['null' => true]);
        $table->create();
        $table->addForeignKey('buyer_id', 'buyers', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        $table->addForeignKey('thing_id', 'things', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        $table->update();

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

        $table = $this->table('drivers', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('name', 'string', ['null' => false]);
        $table->create();

        $table = $this->table('vehicles', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('geocode_id', 'uuid', ['null' => false]);
        $table->create();

        $table = $this->table('drivers_vehicles', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('driver_id', 'uuid', ['null' => false]);
        $table->addColumn('vehicle_id', 'uuid', ['null' => false]);
        $table->addColumn('beginning', 'timestamp', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('ending', 'timestamp', ['default' => 'CURRENT_TIMESTAMP']);
        $table->create();

        $table = $this->table('locations', ['id' => false]);
        $table->addColumn('data', 'json', ['null' => false]);
        $table->create();
    }
}
