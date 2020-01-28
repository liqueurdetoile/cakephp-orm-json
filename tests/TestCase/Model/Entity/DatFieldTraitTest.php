<?php
namespace Lqdt\OrmJson\Test\TestCase\Model\Entity;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Datasource\EntityTrait;

class DatFieldTraitTest extends TestCase
{
    public $Objects;
    public $object;
    public $fixtures = ['Lqdt\OrmJson\Test\Fixture\ObjectsFixture'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => 'Lqdt\OrmJson\Test\Model\Table\ObjectsTable']);
        $this->object = $this->Objects->find()->first();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Objects);
        unset($this->object);
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('Cake\ORM\Entity', $this->object);
        $this->assertEquals(1, $this->object->id);
        $this->assertEquals(['Lqdt\OrmJson\Model\Entity\DatFieldTrait' => 'Lqdt\OrmJson\Model\Entity\DatFieldTrait'], class_uses($this->object));
    }

    public function testMarshalWithNewEntityAndDatField()
    {
        $object = $this->Objects->newEntity([
          'test@attributes' => 'test',
          'nested.test@attributes' => 'deep'
        ]);

        $this->assertEquals([
          'test' => 'test',
          'nested' => [
              'test' => 'deep'
          ]
        ], $object->attributes);
    }

    public function testWithPatchEntity()
    {
        $object = $this->Objects->get(3);
        $object = $this->Objects->patchEntity($object, [
            'test@attributes' => 'test',
            'nested.test@attributes' => 'deep'
        ]);

        $this->assertEquals([
          'test' => 'test',
          'nested' => [
              'test' => 'deep'
          ]
        ], $object->attributes);
    }

    public function testWithPatchEntityAndMerge()
    {
        $object = $this->Objects->get(3);
        $object = $this->Objects->patchEntity($object, [
          'test@attributes' => 'test',
          'nested.test@attributes' => 'deep'
        ])->jsonMerge();

        $this->assertEquals([
          "username" => "test3",
          "email" => "test3@liqueurdetoile.com",
          "boolean" => false,
          "null" => false,
          "float" => 1.2E+15,
          "deep" => [
              "key" => "deepkey2"
          ],
          'test' => 'test',
          'nested' => [
              'test' => 'deep'
          ],
          'group' => 2
        ], $object->attributes);
    }

    public function testjsonGetAsObject()
    {
        $attributes = $this->object->jsonGet('attributes');

        $this->assertEquals(
          [
              'string1',
              10,
              true,
              null,
              1.2,
              ['a','b'],
              'deepkey1'
          ],
          [
              $attributes->string,
              $attributes->integer,
              $attributes->boolean,
              $attributes->null,
              $attributes->decimal,
              $attributes->array,
              $attributes->deep->key,
          ]
        );
    }

    public function testjsonGetAsArray()
    {
        $this->assertEquals(
          [
              'string1',
              10,
              true,
              null,
              1.2,
              ['a','b'],
              ['a' => 'a', 'b' => 'b'],
              'deepkey1',
              ['key' => 'deepkey1']
          ],
          [
              $this->object->jsonGet('string@attributes', true),
              $this->object->jsonGet('integer@attributes', true),
              $this->object->jsonGet('boolean@attributes', true),
              $this->object->jsonGet('null@attributes', true),
              $this->object->jsonGet('decimal@attributes', true),
              $this->object->jsonGet('array@attributes', true),
              $this->object->jsonGet('object@attributes', true),
              $this->object->jsonGet('deep.key@attributes', true),
              $this->object->jsonGet('deep@attributes', true),
          ]
        );
    }

    public function test_jsonSetWithString()
    {
        $this->object->jsonSet('blap@attributes', 'blap');
        $this->assertEquals(
          'blap',
          $this->object->jsonGet('blap@attributes')
        );

        $this->Objects->save($this->object);
        $testSave = $this->Objects->find()->first();
        $this->assertEquals(
          'blap',
          $testSave->jsonGet('blap@attributes')
        );
    }

    public function test_jsonSetWithNull()
    {
        $this->object->jsonSet('blap@attributes', null);
        $this->assertEquals(
          null,
          $this->object->jsonGet('blap@attributes')
        );

        $this->Objects->save($this->object);
        $testSave = $this->Objects->find()->first();
        $this->assertEquals(
          null,
          $testSave->jsonGet('blap@attributes')
        );
    }

    public function test_jsonSetWithArray()
    {
        $this->object->jsonSet([
          'blap@attributes' => 'blap',
          'string@attributes' => 'blap'
        ]);

        $this->assertEquals(
          'blap',
          $this->object->jsonGet('blap@attributes')
        );

        $this->assertEquals(
          'blap',
          $this->object->jsonGet('string@attributes')
        );
    }

    public function test_jsonIsset()
    {
        $this->assertEquals(
          true,
          $this->object->jsonIsset('username@attributes')
        );

        $this->assertEquals(
          false,
          $this->object->jsonGet('blap@attributes', true)
        );
    }

    public function test_jsonUnsetWithArray()
    {
        $this->assertEquals(
          true,
          $this->object->jsonIsset('deep.key@attributes', true)
        );

        $this->assertEquals(
          true,
          $this->object->jsonGet('decimal@attributes', true)
        );

        $this->object->jsonUnset([
          'deep.key@attributes',
          'decimal@attributes'
        ]);

        $this->assertEquals(
          false,
          $this->object->jsonIsset('deep.key@attributes')
        );

        $this->assertEquals(
          false,
          $this->object->jsonGet('decimal@attributes', true)
        );
    }
}
