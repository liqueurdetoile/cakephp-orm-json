<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM;

use Cake\ORM\Entity;

class JsonEntity extends Entity
{
    use \Lqdt\OrmJson\Model\Entity\DatFieldTrait;
}
