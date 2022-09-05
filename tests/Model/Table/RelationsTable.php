<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Lqdt\OrmJson\Test\Model\Entity\Relation;

/**
 * @property \Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo $Agents
 * @property \Lqdt\OrmJson\ORM\Association\DatFieldHasOne $Contacts
 */
class RelationsTable extends DatfieldBehaviorTable
{
    /** @inheritDoc */
    public function initialize(array $options): void
    {
        $this->setTable('agents_clients');
        $this->setEntityClass(Relation::class);

        parent::initialize($options);

        $this->datFieldBelongsTo('Agents', [
          'className' => AgentsTable::class,
          'foreignKey' => 'attributes->agent_id',
        ]);

        $this->datFieldBelongsTo('Clients', [
          'className' => ClientsTable::class,
          'foreignKey' => 'attributes->client_id',
        ]);
    }
}
