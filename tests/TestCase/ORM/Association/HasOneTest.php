<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\ORM\Association;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Fixture\DataGenerator;
use Lqdt\OrmJson\Test\Model\Table\ClientsTable;

class HasOneTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ClientsTable
     */
    public $Clients;

    /**
     * @var array
     */
    public $clients;

    public function setUp(): void
    {
        parent::setUp();

        /**
         * @var \Lqdt\OrmJson\Test\Model\Table\ClientsTable $Clients
         */
        $Clients = TableRegistry::get('Clients', [
          'className' => ClientsTable::class,
        ]);

        $this->Clients = $Clients;
        $generator = new DataGenerator();

        // Generate agents
        $this->clients = $generator
          ->faker('id', 'uuid')
          ->faker('attributes.name', 'name')
          ->faker('contact.id', 'uuid')
          ->callable('contact.attributes.client_id', function ($data) {
              return $data['id'];
          })
          ->faker('contact.attributes.mail', 'email')
          ->generate(20);

        $this->Clients->saveMany($this->Clients->newEntities($this->clients), ['checkExisting' => false]);
    }

    public function tearDown(): void
    {
        unset($this->Clients);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testContain(): void
    {
        $clients = $this->Clients->find()->contain(['Contacts'])->toArray();
        $this->assertNotEmpty($clients);
        /** @var \Lqdt\OrmJson\Test\Model\Entity\Client $client */
        foreach ($clients as $client) {
            $this->assertNotEmpty($client->contact);
            $this->assertEquals($client->id, $client->contact->attributes['client_id']);
        }
    }

    public function testMatching(): void
    {
        $matching = $this->clients[0]['contact']['attributes']['mail'];
        $clients = $this->Clients->find()->matching('Contacts', function ($q) use ($matching) {
            return $q->where(['Contacts.attributes->mail' => $matching]);
        })->toArray();

        $this->assertEquals(1, count($clients));
        $client = $clients[0];
        $this->assertEquals($matching, $client->_matchingData['Contacts']['attributes->mail']);
    }

    public function testInnerJoinWith(): void
    {
        $matching = $this->clients[0]['contact']['attributes']['mail'];
        $clients = $this->Clients->find()->innerJoinWith('Contacts', function ($q) use ($matching) {
            return $q->where(['Contacts.attributes->mail' => $matching]);
        })->toArray();

        $this->assertEquals(1, count($clients));
    }

    public function testCascadeDelete(): void
    {
        $contact = $this->Clients->Contacts->get($this->clients[0]['contact']['id']);
        $cid = $contact['attributes->client_id'];

        $client = $this->Clients->get($cid);
        $this->Clients->deleteOrFail($client);
        $this->assertEmpty($this->Clients->Contacts->find()->where(['id' => $contact->id])->count());
    }
}
