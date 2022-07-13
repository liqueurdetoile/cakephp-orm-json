<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\TestCase\DataGenerator;

class HasOneTest extends TestCase
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
          ->faker('client.attributes.name', 'name')
          ->faker('client.attributes.level', 'randomElement', [0,1,2])
          ->faker('client.attributes.company', 'company')
          ->generate(20);

        $this->Agents->hasOne('Clients', [
          'foreignKey' => 'attributes->agent_id',
        ]);

        $this->Agents->saveManyOrFail($this->Agents->newEntities($this->agents));
    }

    public function tearDown(): void
    {
        $this->Agents = null;
        $this->Clients = null;

        parent::tearDown();
    }

    public function testContain(): void
    {
        $agents = $this->Agents->find()->contain(['Clients'])->toArray();
        $this->assertNotEmpty($agents);
        foreach ($agents as $agent) {
            $this->assertNotEmpty($agent->client);
        }
    }

    public function testMatching(): void
    {
        $agents = $this->Agents->find()->matching('Clients', function ($q) {
            return $q->where(['Clients.attributes->level >' => 1]);
        })->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $this->assertEquals(2, $agent->_matchingData['Clients']['attributes']['level']);
        }
    }

    public function testInnerJoinWith(): void
    {
        $agents = $this->Agents->find()->innerJoinWith('Clients', function ($q) {
            return $q->where(['Clients.attributes->level >' => 1]);
        })->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $this->Agents->loadInto($agent, ['Clients']);
            $this->assertEquals(2, $agent->client->{'attributes->level'});
        }
    }

    public function testCascadeDelete(): void
    {
        $this->Agents->Clients->setDependent(true);
        $agent = $this->Agents->get($this->agents[0]['id'], ['contain' => ['Clients']]);
        $cid = $agent->client->id;
        unset($agent->client);

        $this->assertNotEmpty($this->Agents->Clients->findById($cid)->all());
        $this->Agents->deleteOrFail($agent);
        $this->assertEmpty($this->Agents->findById($this->agents[0]['id'])->all());
        $this->assertEmpty($this->Agents->Clients->findById($cid)->all());
    }
}
