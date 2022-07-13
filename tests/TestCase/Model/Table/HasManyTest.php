<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\Collection\Collection;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\TestCase\DataGenerator;

class HasManyTest extends TestCase
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
          ->faker('attributes.level', 'randomElement', [0,1,2,3,4,5])
          ->generate(20);

        $this->Agents->saveManyOrFail($this->Agents->newEntities($this->agents));
        $this->Clients->saveManyOrFail($this->Clients->newEntities($this->clients));

        $this->Agents->hasMany('Clients', [
          'dependent' => true,
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
        $agents = $this->Agents->find()->contain(['Clients'])->toArray();
        $this->assertNotEmpty($agents);
        foreach ($agents as $agent) {
            $this->assertNotEmpty($agent->clients);
        }
    }

    public function testOrderedContain(): void
    {
        $agents = $this->Agents->find()->contain('Clients', function ($q) {
            return $q->order('Clients.attributes->name');
        })->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $names = (new Collection($agent->clients))->extract('attributes.name')->toArray();
            $original = $names;
            sort($names);
            $this->assertSame($original, $names);
        }
    }

    public function testFilteredContain(): void
    {
        $agents = $this->Agents->find()->contain('Clients', function ($q) {
            return $q->where(['Clients.attributes->level' => 2]);
        })->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            foreach ($agent->clients as $client) {
                $this->assertEquals(2, $client->get('attributes->level'));
            }
        }
    }

    public function testMatching(): void
    {
        $agents = $this->Agents->find()->matching('Clients', function ($q) {
            return $q->where(['Clients.attributes->level' => 2]);
        })->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $this->assertEquals(2, $agent->_matchingData['Clients']['attributes']['level']);
        }
    }

    public function testInnerJoinWith(): void
    {
        $agents = $this->Agents->find()->distinct()->innerJoinWith('Clients', function ($q) {
            return $q->where(['Clients.attributes->level' => 2]);
        })->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $clients = $this->Clients->find()->where([
              'attributes->agent_id' => $agent->id,
              'attributes->level' => 2,
            ])->count();

            $this->assertNotEquals(0, $clients);
        }
    }

    public function testSaveAssociated(): void
    {
        $agent = [
            'attributes' => [
              'name' => 'Superman',
            ],
            'clients' => [
              ['attributes' => ['name' => 'Loïs Lane']],
            ],
        ];

        $agent = $this->Agents->newEntity($agent);
        $agent = $this->Agents->saveOrFail($agent);
        $this->assertNotEmpty($agent->id);
        $this->assertNotEmpty($agent->clients[0]->id);
        $this->assertEquals($agent->clients[0]->{'attributes->agent_id'}, $agent->id);

        // Test append strategy
        $agent->clients = [$this->Clients->newEntity(['attributes' => ['name' => 'Lex Luthor']])];
        $agent = $this->Agents->saveOrFail($agent);
        $this->assertEquals(2, $this->Clients->find()->where(['attributes->agent_id' => $agent->id])->count());

        // test replace strategy
        $this->Agents->Clients->setSaveStrategy('replace');
        $agent->clients = [$this->Clients->newEntity(['attributes' => ['name' => 'Ultron hacked !']])];
        $agent = $this->Agents->saveOrFail($agent);
        $this->assertEquals(1, $this->Clients->find()->where(['attributes->agent_id' => $agent->id])->count());
    }

    public function testCascadeDelete(): void
    {
        $id = $this->agents[0]['id'];
        $agent = $this->Agents->get($id);

        $this->assertNotEquals(0, $this->Clients->find()->where(['attributes->agent_id' => $id])->count());
        $this->Agents->deleteOrFail($agent);
        $this->assertEquals(0, $this->Clients->find()->where(['attributes->agent_id' => $id])->count());
    }

    public function testLinkReplaceAndUnlink(): void
    {
        $agent = $this->Agents->get($this->agents[0]['id']);
        $clients = $this->Clients->find()->toArray();
        $this->Agents->Clients->link($agent, $clients);
        $agent = $this->Agents->get($this->agents[0]['id'], ['contain' => ['Clients']]);

        $this->assertEquals(20, count($agent->clients));

        $agent = $this->Agents->get($this->agents[0]['id']);
        $clients = $this->Clients->find()->limit(5)->toArray();
        $this->Agents->Clients->replace($agent, $clients);
        $agent = $this->Agents->get($this->agents[0]['id'], ['contain' => ['Clients']]);

        $this->assertEquals(5, count($agent->clients));

        $this->Agents->Clients->unlink($agent, $clients);
        $agent = $this->Agents->get($this->agents[0]['id'], ['contain' => ['Clients']]);

        $this->assertEquals(0, count($agent->clients));
    }
}
