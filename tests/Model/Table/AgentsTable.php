<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Lqdt\OrmJson\Test\Model\Entity\Agent;

/**
 * @property \Lqdt\OrmJson\ORM\Association\datFieldHasMany $Clients
 */
class AgentsTable extends DatfieldBehaviorTable
{
    /** @inheritDoc */
    public function initialize(array $options): void
    {
        parent::initialize($options);

        $this->setEntityClass(Agent::class);

        $this->datFieldHasMany('Clients', [
          'className' => ClientsTable::class,
          'foreignKey' => 'attributes->agent_id',
        ]);
    }
}
