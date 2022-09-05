<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;

class DatfieldsTable extends Table
{
    use \Lqdt\OrmJson\ORM\DatFieldAwareTrait;

    public function initialize(array $config): void
    {
        // $this->setTable('objects');
    }
}
