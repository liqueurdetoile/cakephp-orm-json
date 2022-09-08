<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\ORM\Association;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Fixture\DataGenerator;

class BelongsToTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\AgentsTable
     */
    public $Agents;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ClientsTable
     */
    public $Clients;

    /**
     * @var array
     */
    public $agents;

    /**
     * @var array
     */
    public $clients;

    public function setUp(): void
    {
        parent::setUp();

        /** @var \Lqdt\OrmJson\Test\Model\Table\AgentsTable $Agents */
        $Agents = TableRegistry::get('Agents', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\AgentsTable',
        ]);

        /** @var \Lqdt\OrmJson\Test\Model\Table\ClientsTable $Clients */
        $Clients = TableRegistry::get('Clients', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ClientsTable',
        ]);

        $this->Agents = $Agents;
        $this->Clients = $Clients;
        $generator = new DataGenerator();

        // Generate data
        $this->agents = $generator
          ->clear()
          ->faker('id', 'uuid')
          ->faker('attributes.name', 'name')
          ->generate(3);

        $this->clients = $generator
          ->clear()
          ->faker('id', 'uuid')
          ->faker('attributes.agent_id', 'randomElement', array_map(function ($agent) {
            return $agent['id'];
          }, $this->agents))
          ->faker('attributes.company', 'company')
          ->faker('attributes.name', 'name')
          ->generate(20);

        $this->Agents->saveMany($this->Agents->newEntities($this->agents), ['checkExisting' => false]);
        $this->Clients->saveMany($this->Clients->newEntities($this->clients), ['checkExisting' => false]);
    }

    public function tearDown(): void
    {
        unset($this->Agents);
        unset($this->Clients);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testContain(): void
    {
        $clients = $this->Clients->find()->contain(['Agents'])->toArray();
        $this->assertNotEmpty($clients);
        foreach ($clients as $client) {
            $this->assertNotEmpty($client->agent);
            $this->assertEquals($client['attributes']['agent_id'], $client->agent->id);
        }
    }

    public function testContainAndSelect(): void
    {
        $clients = $this->Clients
          ->find()
          ->select(['Clients.id', 'Clients.attributes->agent_id'])
          ->contain('Agents', function ($q) {
              return $q->select(['Agents.id', 'Agents__name' => 'Agents.attributes->name']);
          })
          ->toArray();

        $this->assertNotEmpty($clients);
        foreach ($clients as $client) {
            $this->assertNotEmpty($client->agent);
            $this->assertEquals($client->attributes_agent_id, $client->agent->id);
        }
    }

    public function testMatching(): void
    {
        $agent = $this->agents[0]['attributes']['name'];

        $clients = $this->Clients->find()->matching('Agents', function ($q) use ($agent) {
            return $q->where(['Agents.attributes->name' => $agent]);
        })->toArray();

        $this->assertNotEmpty($clients);

        foreach ($clients as $client) {
            $this->assertEquals($agent, $client->_matchingData['Agents']['attributes']['name']);
        }
    }

    public function testInnerJoinWith(): void
    {
        $id = $this->agents[0]['id'];
        $name = $this->agents[0]['attributes']['name'];

        $clients = $this->Clients->find()->innerJoinWith('Agents', function ($q) use ($name) {
            return $q->where(['Agents.attributes->name' => $name]);
        })->toArray();

        $this->assertNotEmpty($clients);

        foreach ($clients as $client) {
            $this->assertEquals($id, $client->{'attributes->agent_id'});
        }
    }

    public function testSaveAssociated(): void
    {
        $client = [
            'attributes' => [
              'name' => 'Lois Lane',
            ],
            'agent' => [
              'attributes' => [
                'name' => 'superman',
              ],
            ],
        ];

        $client = $this->Clients->newEntity($client);
        /** @var \Lqdt\OrmJson\Test\Model\Entity\Client $client */
        $client = $this->Clients->saveOrFail($client);
        $this->assertNotEmpty($client->id);
        $this->assertNotEmpty($client->agent->id);
        $this->assertEquals($client['attributes->agent_id'], $client->agent->id);
    }
}
