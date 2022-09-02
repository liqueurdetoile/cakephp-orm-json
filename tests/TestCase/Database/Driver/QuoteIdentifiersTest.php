<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\Database\Connection;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class QuoteIdentifiersTest extends TestCase
{
    /**
     * Mocked connection
     *
     * @var \Cake\Database\Connection
     */
    public $connection;

    public function setUp(): void
    {
        parent::setUp();

        $driver = $this->getMockBuilder('Lqdt\OrmJson\Database\Driver\DatFieldMysql')
          ->onlyMethods(['enabled'])
          ->getMock();
        $driver->expects($this->once())
          ->method('enabled')
          ->will($this->returnValue(true));
        $this->connection = new Connection(['driver' => $driver]);
    }

    public function tearDown(): void
    {
        unset($this->connection);

        parent::tearDown();
    }

    public function quoteIdentifierData(): array
    {
        return [
          [
            "data->'$.p'",
            "`data`->'$.p'",
          ],
          [
            "data -> 'p'", // Postgre notation
            "`data` -> 'p'",
          ],
          [
            "JSON_VALUE(jsonCol, '$.info.address.PostCode') AS PostCode", // SQL server notation
            "JSON_VALUE(`jsonCol`, '$.info.address.PostCode') AS `PostCode`",
          ],
          [
            'data->"$.p"',
            '`data`->"$.p"',
          ],
          [
            "Model.data->'$.p'",
            "`Model`.`data`->'$.p'",
          ],
          [
            "data->>'$.p'",
            "`data`->>'$.p'",
          ],
          [
            "JSON_EXTRACT(data, '$.p')",
            "JSON_EXTRACT(`data`, '$.p')",
          ],
          [
            'JSON_EXTRACT(data, "$.p")',
            'JSON_EXTRACT(`data`, "$.p")',
          ],
          [
            "JSON_UNQUOTE(JSON_EXTRACT(data, '$.p'))",
            "JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.p'))",
          ],
        ];
    }

    /** @dataProvider quoteIdentifierData */
    public function testIdentifierQuoting(string $input, string $expected): void
    {
        $result = $this->connection->quoteIdentifier($input);
        $this->assertEquals($expected, $result);
    }

    public function testCompatibility(): void
    {
        $result = $this->connection->quoteIdentifier('name');
        $expected = '`name`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Model.*');
        $expected = '`Model`.*';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Items.No_ 2');
        $expected = '`Items`.`No_ 2`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Items.No_ 2 thing');
        $expected = '`Items`.`No_ 2 thing`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Items.No_ 2 thing AS thing');
        $expected = '`Items`.`No_ 2 thing` AS `thing`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Items.Item Category Code = :c1');
        $expected = '`Items`.`Item Category Code` = :c1';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('MTD()');
        $expected = 'MTD()';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('(sm)');
        $expected = '(sm)';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('name AS x');
        $expected = '`name` AS `x`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Model.name AS x');
        $expected = '`Model`.`name` AS `x`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Function(Something.foo)');
        $expected = 'Function(`Something`.`foo`)';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Function(SubFunction(Something.foo))');
        $expected = 'Function(SubFunction(`Something`.`foo`))';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Function(Something.foo) AS x');
        $expected = 'Function(`Something`.`foo`) AS `x`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('name-with-minus');
        $expected = '`name-with-minus`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('my-name');
        $expected = '`my-name`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Foo-Model.*');
        $expected = '`Foo-Model`.*';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Team.P%');
        $expected = '`Team`.`P%`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Team.G/G');
        $expected = '`Team`.`G/G`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Model.name as y');
        $expected = '`Model`.`name` AS `y`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('nämé');
        $expected = '`nämé`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('aßa.nämé');
        $expected = '`aßa`.`nämé`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('aßa.*');
        $expected = '`aßa`.*';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Modeß.nämé as y');
        $expected = '`Modeß`.`nämé` AS `y`';
        $this->assertEquals($expected, $result);

        $result = $this->connection->quoteIdentifier('Model.näme Datum as y');
        $expected = '`Model`.`näme Datum` AS `y`';
        $this->assertEquals($expected, $result);
    }
}
