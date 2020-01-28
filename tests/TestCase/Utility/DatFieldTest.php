<?php
namespace Lqdt\OrmJson\Test\TestCase\Datasource;

use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Utility\DatField;

class DatFieldTest extends TestCase
{
    public function isDatFieldData()
    {
        return [
          [null, false],
          ['field', false],
          ['Model.field', false],
          ['attribute@field', true],
          ['attribute@Model.field', true]
        ];
    }

    /** @dataProvider isDatFieldData */
    public function testIsDatField($field, $expected)
    {
        $this->assertEquals($expected, DatField::isDatField($field));
    }

    public function getDatFieldPartsData()
    {
        return [
          ['test@field', ['model' => null, 'field' => 'field', 'path' => 'test']],
          ['test.deep@field', ['model' => null, 'field' => 'field', 'path' => 'test.deep']],
          ['test@Model.field', ['model' => 'Model', 'field' => 'field', 'path' => 'test']],
          ['test@Model.field', ['model' => 'Model', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['test@field', ['model' => 'Model', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['test.deep@field', ['model' => 'Model', 'field' => 'field', 'path' => 'test.deep'], 'Model'],
          ['test@Joins.field', ['model' => 'Joins', 'field' => 'field', 'path' => 'test'], 'Model'],
          ['Model.test@field', ['model' => 'Model', 'field' => 'field', 'path' => 'test'], 'Model'], // Special case when using select
          ['Model.test.deep@field', ['model' => 'Model', 'field' => 'field', 'path' => 'test.deep'], 'Model'], // Special case when using select
        ];
    }

    /** @dataProvider getDatFieldPartsData */
    public function testGetDatFieldParts($datfield, $expected, $repository = null)
    {
        $this->assertEquals($expected, DatField::getDatFieldParts($datfield, $repository));
    }

    public function jsonFieldNameData()
    {
        return [
          ['field', 'field'],
          ['Model.field', 'Model.field'],
          ['attribute@field', 'field->"$.attribute"'],
          ['attribute@field', 'field->>"$.attribute"', true],
          ['attribute@field', 'Model.field->"$.attribute"', false, 'Model'],
          ['attribute@field', 'Model.field->>"$.attribute"', true, 'Model'],
        ];
    }

    /** @dataProvider jsonFieldNameData */
    public function testJsonFieldName($field, $expected, $unquote = false, $repository = null)
    {
        $this->assertEquals($expected, DatField::jsonFieldName($field, $unquote, $repository));
    }

    public function buildAliasFromTemplateData()
    {
        return [
          ['attribute@field', '{{model}}{{separator}}{{field}}{{separator}}{{path}}', '_', '_field_attribute'],
          ['attribute@field', '{{field}}{{separator}}{{path}}', '_', 'field_attribute'],
        ];
    }

    /** @dataProvider buildAliasFromTemplateData */
    public function testBuildAliasFromTemplate($datfield, $template, $separator, $expected)
    {
        $this->assertEquals($expected, DatField::buildAliasFromTemplate($datfield, $template, $separator));
    }
}
