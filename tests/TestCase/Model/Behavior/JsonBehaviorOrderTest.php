<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */

class JsonBehaviorOrderTest extends TestCase
{
    public $Users; // Mock up model
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\SortingusersFixture'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Users = TableRegistry::get('Users');
        $this->Users->addBehavior('Lqdt\OrmJson\Model\Behavior\JsonBehavior');
        $this->Users->setEntityClass('Lqdt\OrmJson\Test\TestCase\Model\Entity\User');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Users);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testParsingConditionsAsIdem()
    {
        $query = $this->Users->find('json');
        $conditions = [
          'string@attributes' => 'ASC',
          'integer@attributes' => 'DESC'
        ];

        $this->assertEquals($conditions, $query->parseSortConditions($conditions));
    }

    public function testParsingConditionsAsArrayOfString()
    {
        $query = $this->Users->find('json');
        $conditions = ['string@attributes', 'integer@attributes'];

        $this->assertEquals([
          'string@attributes' => 'ASC',
          'integer@attributes' => 'ASC'
        ], $query->parseSortConditions($conditions));
    }

    public function testParsingConditionsAsString()
    {
        $query = $this->Users->find('json');
        $conditions = 'string@attributes';

        $this->assertEquals([
          'string@attributes' => 'ASC'
        ], $query->parseSortConditions($conditions));
    }

    public function testSortOnString()
    {
        $query = $this->Users->find('json', [
          'json.sort' => [
            'string@attributes' => 'ASC'
          ]
        ]);

        $result = $query->toArray();
        $this->assertEquals('atop', $result[0]->attributes['string']);
        $this->assertEquals('mid', $result[1]->attributes['string']);
        $this->assertEquals('xbottom', $result[2]->attributes['string']);
    }

    public function testSortOnStringWithSelect()
    {
        $query = $this->Users->find('json', [
          'json.sort' => [
            'string@attributes' => 'ASC'
          ]
        ])->select('id');

        $result = $query->toArray();
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
    }

    public function testSortOnStringWithSelectingSameField()
    {
        $query = $this->Users->find('json', [
          'json.sort' => [
            'string@attributes' => 'ASC'
          ]
        ])->jsonSelect(['s' => 'string@attributes']);

        $result = $query->toArray();
        $this->assertEquals('atop', $result[0]->s);
        $this->assertEquals('mid', $result[1]->s);
        $this->assertEquals('xbottom', $result[2]->s);
    }

    public function testSortOnStringWithDeepKey()
    {
        $query = $this->Users->find('json', [
          'json.sort' => [
            'deep.key@attributes' => 'ASC'
          ]
        ]);

        $result = $query->toArray();
        $this->assertEquals('atop', $result[0]->attributes['deep']['key']);
        $this->assertEquals('mid', $result[1]->attributes['deep']['key']);
        $this->assertEquals('xbottom', $result[2]->attributes['deep']['key']);
    }

    public function testSortOnStringDesc()
    {
        $query = $this->Users->find('json', [
          'json.sort' => [
            'string@attributes' => 'DESC'
          ]
        ]);

        $result = $query->toArray();
        $this->assertEquals('xbottom', $result[0]->attributes['string']);
        $this->assertEquals('mid', $result[1]->attributes['string']);
        $this->assertEquals('atop', $result[2]->attributes['string']);
    }

    public function testMultisortOnString()
    {
        $query = $this->Users->find('json', [
          'json.sort' => ['same@attributes', 'string@attributes']
        ]);

        $result = $query->toArray();
        $this->assertEquals('mid', $result[0]->attributes['string']);
        $this->assertEquals('xbottom', $result[1]->attributes['string']);
        $this->assertEquals('atop', $result[2]->attributes['string']);
    }

    public function testSortOnInteger()
    {
        $query = $this->Users->find('json', [
          'json.sort' => 'integer@attributes'
        ])->select('id');

        $result = $query->toArray();
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
    }

    public function testSortOnBoolean()
    {
        $query = $this->Users->find('json', [
          'json.sort' => 'boolean@attributes'
        ])->select('id');

        $result = $query->toArray();
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
    }

    public function testSortOnDecimal()
    {
        $query = $this->Users->find('json', [
          'json.sort' => 'decimal@attributes'
        ])->select('id');

        $result = $query->toArray();
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
    }

    public function testSortOnFloat()
    {
        $query = $this->Users->find('json', [
          'json.sort' => 'float@attributes'
        ])->select('id');

        $result = $query->toArray();
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
    }

    public function testSortOnMixedNull()
    {
        $query = $this->Users->find('json', [
          'json.sort' => 'maybeNull@attributes'
        ]);

        $result = $query->toArray();
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
    }

    public function testSortOnMixedNullDesc()
    {
        $query = $this->Users->find('json', [
          'json.sort' => [
            'maybeNull@attributes' => 'DESC'
          ]
        ]);

        $result = $query->toArray();
        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(3, $result[2]->id);
    }

    public function testSortOnRegularFields()
    {
        $query = $this->Users->jsonQuery();
        $query->jsonOrder(['id' => 'DESC']);

        $result = $query->toArray();
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals(1, $result[2]->id);
    }
}
