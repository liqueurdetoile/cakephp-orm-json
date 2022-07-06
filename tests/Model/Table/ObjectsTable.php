<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;

class ObjectsTable extends Table
{
    protected $_conditions;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior');

        if (!empty($config['conditions'])) {
            $this->_conditions = $config['conditions'];
        }
    }

    public function beforeFind(Event $event, Query $query): Query
    {
        if (!empty($this->_conditions)) {
            $query->where($this->_conditions);
        }

        return $query;
    }

    public function findId($query)
    {
        return $query->select('id');
    }
}
