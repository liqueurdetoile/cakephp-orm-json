<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;
use Lqdt\OrmJson\Model\Behavior\DatFieldBehavior;
use Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity;

class DatfieldBehaviorTable extends Table
{
    public function initialize(array $options): void
    {
        $this->setPrimaryKey('id');
        $this->setEntityClass(DatFieldEntity::class);
        $this->addBehavior(DatFieldBehavior::class, ['upgrade' => true]);
    }
}
