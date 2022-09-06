<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $id
 * @property array  $data
 * @property array  $attributes
 * @property array  $at2
 */
class DatFieldEntity extends Entity
{
    use \Lqdt\OrmJson\Model\Entity\DatFieldTrait;
}
