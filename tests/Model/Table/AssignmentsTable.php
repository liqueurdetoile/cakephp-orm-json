<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;

class AssignmentsTable extends Table
{
    public function initialize(array $options): void
    {
        $this->setTable('drivers_vehicles');
        $this->setPrimaryKey('id');

        $this->belongsTo('Drivers');
        $this->belongsTo('Vehicles');
    }
}
