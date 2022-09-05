<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\ORM\Association;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Fixture\DataGenerator;

class BelongsToManyTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\AgentsTable $Agents
     */
    public $Agents;
    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ClientsTable $Clients
     */
    public $Clients;
    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\RelationsTable $Relations
     */
    public $Relations;

    /**
     * @var array
     */
    public $agents;
    /**
     * @var array
     */
    public $clients;
    /**
     * @var array
     */
    public $relations;

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

        /** @var \Lqdt\OrmJson\Test\Model\Table\RelationsTable $Relations */
        $Relations = TableRegistry::get('Relations', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\RelationsTable',
        ]);

        $this->Agents = $Agents;
        $this->Clients = $Clients;
        $this->Relations = $Relations;

        $this->Agents->datFieldBelongsToMany('Followers', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ClientsTable',
          'foreignKey' => 'attributes->agent_id',
          'targetForeignKey' => 'attributes->client_id',
          'joinTable' => 'Relations',
          'dependent' => true,
        ]);

        $this->Clients->datFieldBelongsToMany('Vendors', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\AgentsTable',
          'foreignKey' => 'attributes->client_id',
          'targetForeignKey' => 'attributes->agent_id',
          'joinTable' => 'Relations',
          'dependent' => true,
        ]);

        $generator = new DataGenerator();

        $this->agents = $generator
          ->clear()
          ->faker('id', 'uuid')
          ->faker('attributes.name', 'name')
          ->generate(5);

        $agents = array_map(function ($agent) {
            return $agent['id'];
        }, $this->agents);

        $this->clients = $generator
          ->clear()
          ->faker('id', 'uuid')
          ->faker('attributes.name', 'name')
          ->faker('attributes.bought', 'randomElement', [100, 500, 1000])
          ->generate(20);

        $clients = array_map(function ($client) {
            return $client['id'];
        }, $this->clients);

        $this->relations = $generator
          ->clear()
          ->faker('id', 'uuid')
          ->faker('attributes.agent_id', 'randomElement', $agents)
          ->faker('attributes.client_id', 'randomElement', $clients)
          ->generate(200);

        $this->Agents->saveManyOrFail($this->Agents->newEntities($this->agents), ['checkExisting' => false]);
        $this->Clients->saveManyOrFail($this->Clients->newEntities($this->clients), ['checkExisting' => false]);
        $this->Relations->saveManyOrFail($this->Relations->newEntities($this->relations), ['checkExisting' => false]);
    }

    public function tearDown(): void
    {
        unset($this->Agents);
        unset($this->Clients);
        unset($this->Relations);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testContain(): void
    {
        $agents = $this->Agents
          ->find()
          ->contain('Followers')
          ->toArray();

        $this->assertNotEmpty($agents);
        foreach ($agents as $agent) {
            $this->assertNotEmpty($agent->followers);
            foreach ($agent->followers as $client) {
                $this->assertEquals($agent->id, $client->_joinData['attributes->agent_id']);
                $this->assertEquals($client->id, $client->_joinData['attributes->client_id']);
            }
        }
    }

    public function testOrderedContain(): void
    {
        $agents = $this->Agents
          ->find()
          ->contain('Followers', function ($q) {
              return $q->order(['Followers.attributes->bought']);
          })
          ->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $prev = null;
            $this->assertNotEmpty($agent->followers);
            foreach ($agent->followers as $client) {
                if ($prev !== null) {
                    $this->assertTrue($client->{'attributes->bought'} <= $prev);
                    $prev = $client->{'attributes->bought'};
                }
            }
        }
    }

    public function testFilteredContain(): void
    {
        $agents = $this->Agents
          ->find()
          ->contain('Followers', function ($q) {
              return $q->where(['Followers.attributes->bought >' => 100]);
          })
          ->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $this->assertNotEmpty($agent->followers);
            foreach ($agent->followers as $client) {
                $this->assertTrue($client->{'attributes->bought'} > 100);
            }
        }
    }

    public function testMatching(): void
    {
        $name = $this->clients[0]['attributes']['name'];

        $agents = $this->Agents
          ->find()
          ->matching('Followers', function ($q) use ($name) {
              return $q->where(['Followers.attributes->name' => $name]);
          })
          ->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $this->assertEquals($name, $agent->_matchingData['Followers']['attributes->name']);
        }
    }

    public function testInnerJoinWith(): void
    {
        $cid = $this->clients[0]['id'];
        $name = $this->clients[0]['attributes']['name'];

        $agents = $this->Agents
          ->find()
          ->distinct()
          ->innerJoinWith('Followers', function ($q) use ($name) {
              return $q->where(['Followers.attributes->name' => $name]);
          })
          ->toArray();

        $this->assertNotEmpty($agents);

        foreach ($agents as $agent) {
            $q = $this->Relations->find()
              ->where([
                ['attributes->agent_id' => $agent->id],
                ['attributes->client_id' => $cid],
              ]);

            $this->assertNotEmpty($q->count());
        }
    }

    public function testSaveAssociated()
    {
        $agent = [
          'attributes' => ['name' => 'Batman'],
          'followers' => [
            ['attributes' => ['name' => 'Superman']],
            ['attributes' => ['name' => 'Loïs Lane']],
          ],
        ];

        $agent = $this->Agents->newEntity($agent);
        $agent = $this->Agents->saveOrFail($agent);

        $this->assertNotEmpty($agent->id);
        $this->assertEquals(2, count($agent->followers));
        foreach ($agent->followers as $client) {
            $this->assertNotEmpty($client->id);
            $this->assertEquals($agent->id, $client->_joinData->{'attributes->agent_id'});
            $this->assertEquals($client->id, $client->_joinData->{'attributes->client_id'});
        }

        // Append mode
        $this->Agents->Followers->setSaveStrategy('append');
        $agent->followers = $this->Clients->newEntities([['attributes' => ['name' => 'Lex Luthor']]]);
        $agent->setDirty('followers', true);
        $agent = $this->Agents->saveOrFail($agent);
        $agent = $this->Agents->loadInto($agent, ['Followers']);

        $this->assertEquals(3, count($agent->followers));

        // // Replace mode
        $this->Agents->Followers->setSaveStrategy('replace');
        $agent->followers = $this->Clients->newEntities([['attributes' => ['name' => 'Ultron hacked !']]]);
        $agent->setDirty('followers', true);
        $agent = $this->Agents->saveOrFail($agent);
        $agent = $this->Agents->loadInto($agent, ['Followers']);

        $this->assertEquals(1, count($agent->followers));
    }

    public function testCascadeDelete(): void
    {
        $id = $this->agents[0]['id'];
        $agent = $this->Agents->get($id);

        $this->assertNotEquals(0, $this->Relations->find()->where(['attributes->agent_id' => $id])->count());
        $this->Agents->deleteOrFail($agent);
        $this->assertEquals(0, $this->Relations->find()->where(['attributes->agent_id' => $id])->count());
    }

    public function testLinkReplaceLinksAndUnlink(): void
    {
        $id = $this->agents[0]['id'];
        $agent = $this->Agents->get($id, ['contain' => ['Followers']]);

        $this->assertNotEquals(0, count($agent->followers));

        $this->Agents->Followers->unlink($agent, $agent->followers);
        $agent = $this->Agents->get($id, ['contain' => ['Followers']]);

        $this->assertEquals(0, count($agent->followers));

        $clients = $this->Clients->find()->toArray();
        $this->Agents->Followers->link($agent, $clients);
        $agent = $this->Agents->get($id, ['contain' => ['Followers']]);

        $this->assertEquals(20, count($agent->followers));

        $clients = $this->Clients->find()->limit(5)->toArray();
        $this->Agents->Followers->replaceLinks($agent, $clients);
        $agent = $this->Agents->get($id, ['contain' => ['Followers']]);

        $this->assertEquals(5, count($agent->followers));
    }
}
