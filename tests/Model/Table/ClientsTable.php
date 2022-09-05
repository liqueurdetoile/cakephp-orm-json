<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Lqdt\OrmJson\Test\Model\Entity\Client;

/**
 * @property \Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo $Agents
 * @property \Lqdt\OrmJson\ORM\Association\DatFieldHasOne $Contacts
 */
class ClientsTable extends DatfieldBehaviorTable
{
    /** @inheritDoc */
    public function initialize(array $options): void
    {
        parent::initialize($options);

        $this->setEntityClass(Client::class);

        $this->datFieldBelongsTo('Agents', [
          'className' => AgentsTable::class,
          'foreignKey' => 'attributes->agent_id',
        ]);

        $this->datFieldHasOne('Contacts', [
          'className' => ContactsTable::class,
          'foreignKey' => 'attributes->client_id',
          'dependent' => true,
        ]);
    }
}
