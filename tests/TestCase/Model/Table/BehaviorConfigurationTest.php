<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Model\Table\ObjectsTable;

class BehaviorConfigurationTest extends TestCase
{
    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\ObjectsTable
     */
    public $Objects = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->Objects = TableRegistry::get('Objects', ['className' => ObjectsTable::class]);
    }

    public function tearDown(): void
    {
        $this->Objects = null;
        parent::tearDown();
    }

    public function testDefaultConfiguration()
    {
        $default = [
          'jsonReplace' => false,
          'keepJsonNested' => false,
          'jsonPropertyTemplate' => '{{field}}{{separator}}{{path}}',
          'jsonSeparator' => '_',
          'parseJsonAsObject' => false,
        ];

        $this->assertSame($default, $this->Objects->getJsonFieldConfig('attributes'));
        $this->assertSame($default, $this->Objects->getJsonFieldConfig('a2'));
    }

    public function testConfigureAllFieldsOnTable()
    {
        $default = [
          'jsonReplace' => false,
          'keepJsonNested' => false,
          'jsonPropertyTemplate' => '{{field}}{{separator}}{{path}}',
          'jsonSeparator' => '_',
          'parseJsonAsObject' => false,
        ];
        $configured = ['keepJsonNested' => true];
        $expected = array_merge($default, $configured);

        $this->Objects->configureJsonFields($configured);
        $this->assertSame($expected, $this->Objects->getJsonFieldConfig('attributes'));
        $this->assertSame($expected, $this->Objects->getJsonFieldConfig('at2'));
    }

    public function testConfigureTargettedFieldsOnTable()
    {
        $default = [
          'jsonReplace' => false,
          'keepJsonNested' => false,
          'jsonPropertyTemplate' => '{{field}}{{separator}}{{path}}',
          'jsonSeparator' => '_',
          'parseJsonAsObject' => false,
        ];
        $configured = ['keepJsonNested' => true, 'jsonFields' => ['at2']];
        $expected = array_merge($default, $configured);

        $this->Objects->configureJsonFields($configured);
        $this->assertSame($default, $this->Objects->getJsonFieldConfig('attributes'));
        $this->assertSame($expected, $this->Objects->getJsonFieldConfig('at2'));
    }

    public function testConfigureAllFieldsOnRuntime()
    {
        $default = [
          'jsonReplace' => false,
          'keepJsonNested' => false,
          'jsonPropertyTemplate' => '{{field}}{{separator}}{{path}}',
          'jsonSeparator' => '_',
          'parseJsonAsObject' => false,
        ];
        $configured = ['keepJsonNested' => true];
        $expected = array_merge($default, $configured);

        $this->assertSame($expected, $this->Objects->getJsonFieldConfig('attributes', $configured));
        $this->assertSame($default, $this->Objects->getJsonFieldConfig('attributes'));
        $this->assertSame($expected, $this->Objects->getJsonFieldConfig('at2', $configured));
        $this->assertSame($default, $this->Objects->getJsonFieldConfig('at2'));
    }

    public function testConfigureAllTargettedFieldsOnRuntime()
    {
        $default = [
          'jsonReplace' => false,
          'keepJsonNested' => false,
          'jsonPropertyTemplate' => '{{field}}{{separator}}{{path}}',
          'jsonSeparator' => '_',
          'parseJsonAsObject' => false,
        ];
        $configured = ['keepJsonNested' => true, 'jsonFields' => ['at2']];
        $expected = array_merge($default, $configured);

        $this->assertSame($default, $this->Objects->getJsonFieldConfig('attributes', $configured));
        $this->assertSame($expected, $this->Objects->getJsonFieldConfig('at2', $configured));
        $this->assertSame($default, $this->Objects->getJsonFieldConfig('at2'));
    }
}
