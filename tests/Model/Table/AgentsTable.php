<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Model\Table;

use Lqdt\OrmJson\Test\Model\Entity\Agent;

/**
 * @property \Lqdt\OrmJson\ORM\Association\datFieldHasMany $Clients
 * @property \Lqdt\OrmJson\ORM\Association\datFieldBelongsToMany $Followers
 * @method \Lqdt\OrmJson\Test\Model\Entity\Agent get(string $id, array $options = [])
 * @method \Lqdt\OrmJson\Test\Model\Entity\Agent loadInto(\Lqdt\OrmJson\Test\Model\Entity\Agent $agent, array $models)
 * @method \Lqdt\OrmJson\Test\Model\Entity\Agent newEntity(array $data, array $options = [])
 * @method \Lqdt\OrmJson\Test\Model\Entity\Agent saveOrFail(\Lqdt\OrmJson\Test\Model\Entity\Agent $agent, array $options = [])
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
