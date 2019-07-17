<?php
namespace Lqdt\OrmJson\Test\TestCase\ORM;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;

class JsonQueryTest extends TestCase
{
    public $Users; // Mock up model
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\UsersFixture'];

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

    public function testConstructor()
    {
        $query = $this->Users->jsonQuery();
        $this->assertInstanceOf('Lqdt\OrmJson\ORM\JsonQuery', $query);
    }

    public function testJsonFieldName()
    {
        $query = $this->Users->jsonQuery();
        $this->assertEquals(
          'Users.attributes->"$.key.deep"',
          $query->jsonFieldName('key.deep@Users.attributes')
        );
    }

    public function testJsonFieldsNameInString()
    {
        $query = $this->Users->jsonQuery();
        $this->assertEquals(
          "Users.attributes->\"$.username\"='test' AND Users.attributes->\"$.deep.mail\" NOT LIKE 'test@test.com'",
          $query->jsonFieldsNameinString("username@Users.attributes='test' AND deep.mail@Users.attributes NOT LIKE 'test@test.com'")
        );
    }

    public function testJsonFieldsNameInStringWithMail()
    {
        $query = $this->Users->jsonQuery();
        $this->assertEquals(
          "attributes->\"$.deep.mail\" NOT LIKE 'test@test.com'",
          $query->jsonFieldsNameinString("deep.mail@attributes NOT LIKE 'test@test.com'")
        );
    }
}
