<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;
use Lqdt\OrmJson\Model\Behavior\DatFieldBehavior;

class LocationsTable extends Table
{
    public function initialize(array $options): void
    {
        $this->setTable('locations');
        $this->addBehavior(DatFieldBehavior::class, ['upgrade' => true]);
    }
}
