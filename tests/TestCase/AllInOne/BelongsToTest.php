<?php
namespace Lqdt\OrmJson\Test\TestCase\AllInOne;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class BelongsToTest extends TestCase
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

        $this->Clients = TableRegistry::get('Clients', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'objects',
          'alias' => 'Clients',
          'conditions' => ['Clients.model_id' => $cmid],
        ]);

        $this->Clients->belongsTo('Agents', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable',
          'table' => 'objects',
          'alias' => 'Agents',
          'conditions' => ['Agents.model_id' => $amid],
          'foreignKey' => 'agent_id@attributes'
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
        $query = $this->Clients->find()->contain('Agents');
        $clients = $query->toArray();

        $this->assertNotEmpty($clients);
        foreach ($clients as $client) {
            $this->assertEquals($client->attributes['agent_id'], $client->agent->id);
        }
    }

    public function testFetchWhere()
    {
        $query = $this->Clients->find()->contain('Agents')->where(['title@Agents.attributes' => 'Mr.']);
        $clients = $query->toArray();

        $this->assertNotEmpty($clients);
        foreach ($clients as $client) {
            $this->assertEquals($client->attributes['agent_id'], $client->agent->id);
            $this->assertEquals('Mr.', $client->agent->attributes['title']);
        }
    }

    public function testFetchInnerJoinWith()
    {
        $query = $this->Clients->find()->innerJoinWith('Agents', function ($q) {
            return $q->where(['title@Agents.attributes' => 'Mr.']);
        });

        $this->assertEquals(1, $query->count());
    }

    public function testCount()
    {
        $query = $this->Clients->find()->InnerJoinWith('Agents', function ($q) {
            return $q->where(['title@Agents.attributes' => 'Mr.']);
        });

        $query->select(['count' => $query->func()->count('*')]);

        $this->assertEquals(1, $query->first()->count);
    }
}
