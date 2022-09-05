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

        $table = $this->table('contacts', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid');
        $table->addColumn('attributes', 'json', ['null' => true]);
        $table->create();

        $table = $this->table('agents_clients', ['id' => false, 'primary_key' => ['id']]);
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
