<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;

class VehiclesTable extends Table
{
    use \Lqdt\OrmJson\ORM\DatFieldAwareTrait;

    public function initialize(array $options): void
    {
        $this->setTable('vehicles');
        $this->setPrimaryKey('id');

        $this->BelongsToMany('Drivers', [
          'through' => 'Assignments',
        ]);

        $this->hasMany('Assignments');

        $this->datFieldHasMany('Locations', [
          'bindingKey' => 'geocode_id',
          'foreignKey' => 'data->vehicle.id',
        ]);
    }
}
