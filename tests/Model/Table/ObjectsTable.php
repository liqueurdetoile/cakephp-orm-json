<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Cake\ORM\Table;
use Lqdt\OrmJson\Model\Behavior\DatFieldBehavior;
use Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity;

/**
 * @method \Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity get(string $id, array $options = [])
 * @method \Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity newEntity(array $data, array $options = [])
 * @method \Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity[] newEntities(array $data, array $options = [])
 * @method \Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity[] saveMany(array $entities, array $options = [])
 * @method \Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity saveOrFail(\Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity $agent, array $options = [])
 * @method \Lqdt\OrmJson\Database\Schema\DatFieldTableSchemaInterface getSchema()
 * @mixin \Lqdt\OrmJson\Model\Behavior\DatFieldBehavior
 */
class ObjectsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        $this->setTable('objects');
        $this->setPrimaryKey('id');
        $this->setEntityClass(DatFieldEntity::class);
        $this->addBehavior(DatFieldBehavior::class, ['upgrade' => true]);
    }
}
