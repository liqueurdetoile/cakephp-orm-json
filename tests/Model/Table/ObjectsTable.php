<?php
namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;

class ObjectsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior');
        $this->setEntityClass('Lqdt\OrmJson\Test\Model\Entity\ObjectEntity');
    }

    public function findId($query)
    {
        return $query->select('id');
    }
}
