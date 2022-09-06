<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\DatField;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Lqdt\OrmJson\DatField\Exception\MissingPathInDataDatFieldException;
use Lqdt\OrmJson\Test\Model\DatFieldParser;

class DatFieldParserTraitTest extends TestCase
{
    /**
     * Mocked trait
     *
     * @var \Lqdt\OrmJson\Test\Model\DatFieldParser
     */
    public $parser;

    public function setUp(): void
    {
        parent::setUp();
        $this->parser = new DatFieldParser();
    }

    public function tearDown(): void
    {
        unset($this->parser);
        parent::tearDown();
    }

    public function testGetDatFieldPart(): void
    {
        $this->assertEquals('data', $this->parser->getDatFieldPart('field', 'test@data'));
        $this->assertEquals('test.var', $this->parser->getDatFieldPart('path', 'data->test.var'));
        $this->assertEquals('Objects', $this->parser->getDatFieldPart('model', 'Objects.data->test.var'));
        $this->assertEquals('Objects', $this->parser->getDatFieldPart('model', 'test@Objects.data'));
        $this->expectException(\Exception::class);
        $this->parser->getDatFieldPart('silly', 'test@data');
    }

    public function getDatFieldValueInDataData(): array
    {
        $data = [
          'id' => Text::uuid(),
          'attributes' => [
            'arr' => [1],
            'arr2' => [
              ['k' => 1],
              ['k' => 2],
            ],
            'arr3' => [
              [
                ['k' => 1],
                ['k' => 2],
              ],
            ],
            'arr4' => [

            ],
            'withUppercase' => null,
            'deep' => [
              'key' => true,
            ],
          ],
        ];

        $entity = new Entity($data);
        $entity->clean();

        return [
          ['id', $data, $data['id']],
          ['missing', $data, MissingPathInDataDatFieldException::class],
          ['deep.key@attributes', $data, true],
          ['attributes->deep.key', $data, true],
          ['attributes->withUppercase', $data, null],
          ['attributes->arr[0]', $data, 1],
          ['attributes->missing', $data, null],
          ['missing->missing', $data, null],
          ['missing->missing', $data, MissingPathInDataDatFieldException::class],
          ['id', $entity, $data['id']],
          ['deep.key@attributes', $entity, true],
          ['attributes->deep.key', $entity, true],
          ['attributes->arr[0]', $entity, 1],
          ['missing->missing', $entity, null],
          ['missing->missing', $entity, MissingPathInDataDatFieldException::class],
          ['attributes->arr2[*]', $data, [['k' => 1], ['k' => 2]]],
          ['attributes->arr2[*].k', $data, [1, 2]],
          ['attributes->arr2[1].k', $data, 2],
          ['attributes->arr2[*]', $entity, [['k' => 1], ['k' => 2]]],
          ['attributes->arr2[*].k', $entity, [1, 2]],
          ['attributes->arr3[*]', $entity, [[['k' => 1], ['k' => 2]]]],
          ['attributes->arr3[0]', $entity, [['k' => 1], ['k' => 2]]],
          ['attributes->arr3[*][*]', $entity, [['k' => 1], ['k' => 2]]],
          ['attributes->arr3[*][*].k', $entity, [1, 2]],
          ['attributes->arr3[*][0].k', $entity, [1]],
          ['attributes->arr3[0][0].k', $entity, 1],
        ];
    }

    /**
     * @param string $key       Field or Datfield
     * @param array|\Cake\ORM\Entity $data      Data
     * @param string | null $expected  Expected
     * @dataProvider getDatFieldValueInDataData
     */
    public function testGetDatFieldValueInData(string $key, $data, $expected): void
    {
        $throwIfMissing = $expected === MissingPathInDataDatFieldException::class;

        if ($throwIfMissing) {
            $this->expectException($expected);
        }

        $this->assertSame($expected, $this->parser->getDatFieldValueInData($key, $data, $throwIfMissing));
    }

    /**
     * @param string $key       Field or Datfield
     * @param array|\Cake\ORM\Entity $data      Data
     * @dataProvider getDatFieldValueInDataData
     */
    public function testHasDatFieldPathInData($key, $data): void
    {
        $missing = strpos($key, 'missing') !== false;

        if ($missing) {
            $this->assertFalse($this->parser->hasDatFieldPathInData($key, $data));
        } else {
            $this->assertTrue($this->parser->hasDatFieldPathInData($key, $data));
        }
    }

    public function hasReferenceWithGetDatFieldValueInDataData(): array
    {
        $data = [
          'id' => Text::uuid(),
          'attributes' => [
            'arr' => [1],
            'arr2' => [
              ['k' => 1],
              ['k' => 2],
            ],
            'deep' => [
              'key' => true,
            ],
          ],
        ];

        return [
            ['id', $data, 'hacked'],
            ['deep.key@attributes', $data, 'hacked'],
            ['attributes->deep.key', $data, 'hacked'],
            ['attributes->arr[0]', $data, 'hacked'],
            ['attributes->arr2[*]', $data, ['hacked', 'hacked']],
            ['attributes->arr2[*].k', $data, ['hacked', 'hacked']],
        ];
    }

    /**
     * @dataProvider hasReferenceWithGetDatFieldValueInDataData
     * @param string $key       [description]
     * @param array  $data      [description]
     * @param array|string $expected  [description]
     */
    public function testHasReferenceWithGetDatFieldValueInData(string $key, array $data, $expected): void
    {
        // We must generate entity differently as it will hacked by reference if set up in data provider
        $entity = new Entity($data);
        $entity->clean();

        $target = &$this->parser->getDatFieldValueInData($key, $data);

        if (is_array($target)) {
            foreach ($target as &$item) {
                $item = 'hacked';
            }
        } else {
            $target = 'hacked';
        }

        $this->assertSame($expected, $this->parser->getDatFieldValueInData($key, $data));

        $target = &$this->parser->getDatFieldValueInData($key, $entity);

        if (is_array($target)) {
            foreach ($target as &$item) {
                $item = 'hacked';
            }
        } else {
            $target = 'hacked';
        }

        $this->assertSame($expected, $this->parser->getDatFieldValueInData($key, $data));
    }

    public function testSetDatFieldValueInData(): void
    {
        $data = [
          'id' => Text::uuid(),
          'attributes' => [
            'test' => true,
            'arr' => [1],
            'arr2' => [
              ['k' => 1],
              ['k' => 2],
            ],
            'deep' => [
              'key' => true,
            ],
          ],
        ];

        $entity = new Entity($data);
        $entity->clean();

        // Checks on data array
        $data = $this->parser->setDatFieldValueInData('id', false, $data);
        $this->assertFalse($data['id']);

        $data = $this->parser->setDatFieldValueInData('attributes->deep.key', false, $data);
        $this->assertFalse($data['attributes']['deep']['key']);

        $data = $this->parser->setDatFieldValueInData('attributes->arr[0]', false, $data);
        $this->assertFalse($data['attributes']['arr'][0]);

        $data = $this->parser->setDatFieldValueInData('attributes->arr2[*].k', false, $data);
        $this->assertFalse($data['attributes']['arr2'][0]['k']);
        $this->assertFalse($data['attributes']['arr2'][1]['k']);

        // Creates paths
        $data = $this->parser->setDatFieldValueInData('attributes->missing', true, $data);
        $this->assertTrue($data['attributes']['missing']);

        $data = $this->parser->setDatFieldValueInData('at2->very.missing', true, $data);
        $this->assertTrue($data['at2']['very']['missing']);

        $this->parser->setDatFieldValueInData('at2->very.missing', true, $entity);
        $this->assertTrue($entity['at2']['very']['missing']);

        $data = $this->parser->setDatFieldValueInData('attributes->other.missing', true, $data);
        $this->assertTrue($data['attributes']['other']['missing']);

        $data = $this->parser->setDatFieldValueInData('attributes->marr[*]', true, $data);
        $this->assertTrue($data['attributes']['marr'][0]);

        $data = $this->parser->setDatFieldValueInData('attributes->marr[3]', true, $data);
        $this->assertTrue($data['attributes']['marr'][3]);

        $data = $this->parser->setDatFieldValueInData('attributes->dmarr[*][*]', true, $data);
        $this->assertTrue($data['attributes']['dmarr'][0][0]);

        $data = $this->parser->setDatFieldValueInData('attributes->dmarr2[*][*].k', true, $data);
        $this->assertTrue($data['attributes']['dmarr2'][0][0]['k']);

        // Checks on entity object
        $entity = $this->parser->setDatFieldValueInData('id', false, $entity);
        $this->assertFalse($entity['id']);

        $entity = $this->parser->setDatFieldValueInData('attributes->deep.key', false, $entity);
        $this->assertFalse($entity['attributes']['deep']['key']);

        $entity = $this->parser->setDatFieldValueInData('attributes->arr[0]', false, $entity);
        $this->assertFalse($entity['attributes']['arr'][0]);

        $entity = $this->parser->setDatFieldValueInData('attributes->arr2[*].k', false, $entity);
        $this->assertFalse($entity['attributes']['arr2'][0]['k']);
        $this->assertFalse($entity['attributes']['arr2'][1]['k']);

        // Throw on missing
        $this->expectException(\RuntimeException::class);
        $data = $this->parser->setDatFieldValueInData('attributes->reallyMissing', true, $data, true);
    }

    public function testDeleteDatFieldValueInData(): void
    {
        $data = [
          'id' => Text::uuid(),
          'attributes' => [
            'test' => true,
            'arr' => [1],
            'arr2' => [
              ['k' => 1],
              ['k' => 2],
            ],
            'deep' => [
              'key' => true,
            ],
          ],
        ];

        $entity = new Entity($data);
        $entity->clean();

        $this->parser->deleteDatFieldValueInData('attributes->test', $data);
        $this->assertFalse(array_key_exists('test', $data['attributes']));

        $this->parser->deleteDatFieldValueInData('attributes->deep.test', $data);
        $this->assertFalse(array_key_exists('test', $data['attributes']['deep']));

        $this->parser->deleteDatFieldValueInData('attributes->arr2[*].k', $data);
        $this->assertSame([[], []], $data['attributes']['arr2']);

        $this->parser->deleteDatFieldValueInData('attributes->arr2[*]', $data);
        $this->assertSame([], $data['attributes']['arr2']);

        $this->parser->deleteDatFieldValueInData('attributes->arr[0]', $data);
        $this->assertSame([], $data['attributes']['arr']);

        $this->parser->deleteDatFieldValueInData('attributes->test', $entity);
        $this->assertFalse(array_key_exists('test', $entity['attributes']));

        $this->parser->deleteDatFieldValueInData('attributes->deep.test', $entity);
        $this->assertFalse(array_key_exists('test', $entity['attributes']['deep']));

        $this->parser->deleteDatFieldValueInData('attributes->arr2[*].k', $entity);
        $this->assertSame([[], []], $entity['attributes']['arr2']);

        $this->parser->deleteDatFieldValueInData('attributes->arr2[*]', $entity);
        $this->assertSame([], $entity['attributes']['arr2']);

        $this->parser->deleteDatFieldValueInData('attributes->arr[0]', $entity);
        $this->assertSame([], $entity['attributes']['arr']);
    }

    public function isDatFieldData(): array
    {
        return [
          [null, 0],
          ['field', 0],
          ['Model.field', 0],
          ['attribute@field', 1],
          ['attribute@Model.field', 1],
          ['field->attribute', 2],
          ['Model.field->attribute', 2],
          ['arr[0]@attributes', 1],
          ['attributes->arr[0]', 2],
          ['attributes->**key', 2],
        ];
    }

    /**
     * @dataProvider isDatFieldData
     */
    public function testIsDatField(?string $field, int $expected): void
    {
        $this->assertEquals($expected, $this->parser->isDatField($field));
    }

    public function parseDatFieldData(): array
    {
        return [
          ['test@field', ['model' => null, 'field' => 'field', 'path' => 'test']],
          ['field->test', ['model' => null, 'field' => 'field', 'path' => 'test']],
          ['test.deep@field', ['model' => null, 'field' => 'field', 'path' => 'test.deep']],
          ['field->test.deep', ['model' => null, 'field' => 'field', 'path' => 'test.deep']],
          ['test@Model.field', ['model' => 'Model', 'field' => 'field', 'path' => 'test']],
          ['Model.field->test', ['model' => 'Model', 'field' => 'field', 'path' => 'test']],
          ['test@Model.field', ['model' => 'Model', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['Model.field->test', ['model' => 'Model', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['test@field', ['model' => 'Model', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['field->test', ['model' => 'Model', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['test.deep@field', ['model' => 'Model', 'field' => 'field', 'path' => 'test.deep'], 'Model'],
          ['field->test.deep', ['model' => 'Model', 'field' => 'field', 'path' => 'test.deep'], 'Model'],
          ['test@Joins.field', ['model' => 'Joins', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['Joins.field->test', ['model' => 'Joins', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['test.deep@field', ['model' => 'Model', 'field' => 'field', 'path' => 'test.deep'], 'Model'],
          ['field->test.deep', ['model' => 'Model', 'field' => 'field', 'path' => 'test.deep'], 'Model'],
          ['Joins.field->test', ['model' => 'Joins', 'field' => 'field', 'path' => 'test'], 'Model'],
        ];
    }

    /**
     * @dataProvider parseDatFieldData
     */
    public function testParseDatField(string $datfield, array $expected, ?string $repository = null): void
    {
        $this->assertEquals($expected, $this->parser->parseDatField($datfield, $repository));
    }

    public function renderFromDatFieldAndTemplateData(): array
    {
        return [
          ['attribute@field', '{{model}}{{separator}}{{field}}{{separator}}{{path}}', '_', '_field_attribute'],
          ['attribute@field', '{{field}}{{separator}}{{path}}', '_', 'field_attribute'],
          ['field->attribute', '{{field}}{{separator}}{{path}}', '_', 'field_attribute'],
        ];
    }

    /**
     * @dataProvider renderFromDatFieldAndTemplateData
     */
    public function testRenderFromDatFieldAndTemplate(string $datfield, string $template, string $separator, string $expected): void
    {
        $this->assertEquals($expected, $this->parser->renderFromDatFieldAndTemplate($datfield, $template, $separator));
    }
}
