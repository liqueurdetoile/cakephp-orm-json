<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Datasource;

use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

class DatFieldAwareTraitTest extends TestCase
{
    public $parser = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->parser = $this->getObjectForTrait(DatFieldAwareTrait::class);
    }

    public function tearDown(): void
    {
        $this->parser = null;
        parent::tearDown();
    }

    public function isDatFieldData()
    {
        return [
          [null, 0],
          ['field', 0],
          ['Model.field', 0],
          ['attribute@field', 1],
          ['attribute@Model.field', 1],
          ['field->attribute', 2],
          ['Model.field->attribute', 2],
        ];
    }

    /**
     * @dataProvider isDatFieldData
     */
    public function testIsDatField($field, $expected)
    {
        $this->assertEquals($expected, $this->parser->isDatField($field));
    }

    public function parseDatFieldData()
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
    public function testParseDatField($datfield, $expected, $repository = null)
    {
        $this->assertEquals($expected, $this->parser->parseDatField($datfield, $repository));
    }

    public function translateToJsonExtractData()
    {
        return [
          ['field', 'field'],
          ['Model.field', 'Model.field'],
          ['attribute@field', 'field->"$.attribute"'],
          ['field->attribute', 'field->"$.attribute"'],
          ['attribute@field', 'field->>"$.attribute"', true],
          ['field->attribute', 'field->>"$.attribute"', true],
          ['attribute@field', 'Model.field->"$.attribute"', false, 'Model'],
          ['field->attribute', 'Model.field->"$.attribute"', false, 'Model'],
          ['attribute@field', 'Model.field->>"$.attribute"', true, 'Model'],
          ['field->attribute', 'Model.field->>"$.attribute"', true, 'Model'],
        ];
    }

    /**
     * @dataProvider translateToJsonExtractData
     */
    public function testTranslateToJsonExtract($field, $expected, $unquote = false, $repository = null)
    {
        $this->assertEquals($expected, $this->parser->translateToJsonExtract($field, $unquote, $repository));
    }

    public function translateSQLToJsonExtractData()
    {
        return [
          ['field', 'field'],
          ['Model.field', 'Model.field'],
          ['attribute@field', 'field->"$.attribute"'],
          ['field->attribute', 'field->"$.attribute"'],
          [
            'username@attributes NOT IN (\'"test2"\', \'"test3"\')',
            'attributes->"$.username" NOT IN (\'"test2"\', \'"test3"\')',
          ],
          [
            'attributes->username NOT IN (\'"test2"\', \'"test3"\')',
            'attributes->"$.username" NOT IN (\'"test2"\', \'"test3"\')',
          ],
          [
            'username@attributes NOT LIKE "test" AND last.login@attributes < DATE_SUB(NOW(), INTERVAL 1 DAY)',
            'attributes->"$.username" NOT LIKE "test" AND attributes->"$.last.login" < DATE_SUB(NOW(), INTERVAL 1 DAY)',
          ],
          [
            'attributes->username NOT LIKE "test" AND attributes->"$.last.login" < DATE_SUB(NOW(), INTERVAL 1 DAY)',
            'attributes->"$.username" NOT LIKE "test" AND attributes->"$.last.login" < DATE_SUB(NOW(), INTERVAL 1 DAY)',
          ],
        ];
    }

    /**
     * @dataProvider translateSQLToJsonExtractData
     */
    public function testTranslateSQLToJsonExtract($sql, $expected, $unquote = false, $repository = null)
    {
        $this->assertEquals($expected, $this->parser->translateSQLToJsonExtract($sql, $unquote, $repository));
    }

    public function renderFromDatFieldAndTemplateData()
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
    public function testRenderFromDatFieldAndTemplate($datfield, $template, $separator, $expected)
    {
        $this->assertEquals($expected, $this->parser->renderFromDatFieldAndTemplate($datfield, $template, $separator));
    }
}
