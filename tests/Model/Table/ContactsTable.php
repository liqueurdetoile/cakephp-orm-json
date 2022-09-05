<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Lqdt\OrmJson\Test\Model\Entity\Contact;

/**
 * @property \Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo $Clients
 */
class ContactsTable extends DatfieldBehaviorTable
{
    /** @inheritDoc */
    public function initialize(array $options): void
    {
        parent::initialize($options);

        $this->setEntityClass(Contact::class);
        $this->setTable('contacts');

        $this->datFieldBelongsTo('Clients', [
          'className' => ClientsTable::class,
          'foreignKey' => 'attributes->client_id',
        ]);
    }
}
