<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\TestCase\DataGenerator;

class BelongsToTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    public $Agents;
    public $Clients;

    public $agents;
    public $clients;

    public function setUp(): void
    {
        parent::setUp();

        $this->Agents = TableRegistry::get('Agents', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'agents',
        ]);

        $this->Clients = TableRegistry::get('Clients', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'clients',
        ]);

        $generator = new DataGenerator();

        // Generate agents
        $this->agents = $generator
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

        $this->Agents->saveManyOrFail($this->Agents->newEntities($this->agents));
        $this->Clients->saveManyOrFail($this->Clients->newEntities($this->clients));

        $this->Clients->belongsTo('Agents', [
          'foreignKey' => 'attributes->agent_id',
        ]);
    }

    public function tearDown(): void
    {
        $this->Agents = null;
        $this->Clients = null;

        parent::tearDown();
    }

    public function testContain(): void
    {
        $clients = $this->Clients->find()->contain(['Agents'])->toArray();
        $this->assertNotEmpty($clients);
        foreach ($clients as $client) {
            $this->assertNotEmpty($client->agent);
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
        $client = $this->Clients->saveOrFail($client);
        $this->assertNotEmpty($client->id);
        $this->assertNotEmpty($client->agent->id);
        $this->assertEquals($client->{'attributes->agent_id'}, $client->agent->id);
    }
}
