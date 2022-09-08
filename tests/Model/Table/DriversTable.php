<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;

class DriversTable extends Table
{
    use \Lqdt\OrmJson\ORM\DatFieldAwareTrait;

    public function initialize(array $options): void
    {
        $this->setTable('drivers');
        $this->setPrimaryKey('id');

        $this->BelongsToMany('Vehicles', [
          'through' => 'Assignments',
        ]);

        $this->hasMany('Assignments');
    }
}
