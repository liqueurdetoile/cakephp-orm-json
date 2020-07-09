<?php
namespace Lqdt\OrmJson\Test\TestCase\AllInOne;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class HasManyTest extends TestCase
{
    public $Clients;
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\AllInOneFixture'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $amid = 'c22eef21-0468-4e00-a436-f4c90e1c1ed0';
        $cmid = 'c22eef21-0468-4e00-a436-f4c90e1c1ed1';

        $this->Agents = TableRegistry::get('Agents', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'objects',
          'alias' => 'Agents',
          'conditions' => ['Agents.model_id' => $amid],
        ]);

        $this->Agents->HasDatMany('Clients', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'objects',
          'alias' => 'Clients',
          'conditions' => ['Clients.model_id' => $cmid],
          'foreignKey' => 'agent_id@attributes',
          // 'strategy' => 'subquery'
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Clients);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testFetchAll()
    {
        $query = $this->Agents->find()->contain('Clients');
        $agents = $query->toArray();

        $this->assertNotEmpty($agents);
        foreach ($agents as $agent) {
            $this->assertNotEmpty($agent->clients);
            foreach ($agent->clients as $client) {
                $this->assertEquals($agent->id, $client->attributes['agent_id']);
            }
        }
    }

    public function testFetchWhere()
    {
        $expected = $this->Agents->Clients->find()->where(['title@Clients.attributes' => 'Mr.'])->count();

        $query = $this->Agents->find()->contain('Clients', function ($q) {
            return $q->where(['title@Clients.attributes' => 'Mr.']);
        });
        $agents = $query->toArray();

        $this->assertNotEmpty($agents);

        $count = 0;
        $clients = 0;
        foreach ($agents as $agent) {
            $clients += count($agent->clients);
            foreach ($agent->clients as $client) {
                $this->assertEquals($agent->id, $client->attributes['agent_id']);
                $this->assertEquals('Mr.', $client->attributes['title']);
            }
        }

        $this->assertEquals($expected, $clients);
    }

    public function testFetchInnerJoinWithAndCount()
    {
        $query = $this->Agents->find()->group('Agents.id')->innerJoinWith('Clients', function ($q) {
            return $q->where(['title@Clients.attributes' => 'Mr.']);
        });

        // debug(json_encode($query->all(), JSON_PRETTY_PRINT));
        $this->assertEquals(5, $query->count());
    }
}
