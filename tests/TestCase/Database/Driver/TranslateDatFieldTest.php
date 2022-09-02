<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Database\DatFieldDriverInterface;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class TranslateDatFieldTest extends TestCase
{
    public function getDriver(string $classname): DatFieldDriverInterface
    {
        $driver = $this->getMockBuilder($classname)
          ->onlyMethods(['enabled'])
          ->getMock();

        return $driver;
    }

    public function translateDatFieldMysqlData(): array
    {
        return [
          [
            'data->p',
            false,
            "JSON_EXTRACT(data, '$.p')",
          ],
          [
            'data->[*]',
            false,
            "JSON_EXTRACT(data, '$[*]')",
          ],
          [
            'data->**.p',
            false,
            "JSON_EXTRACT(data, '$**.p')",
          ],
          [
            'data->**.p',
            false,
            "JSON_EXTRACT(data, '$**.p')",
          ],
          [
            'data->p',
            true,
            "JSON_UNQUOTE(JSON_EXTRACT(data, '$.p'))",
          ],
        ];
    }

    /** @dataProvider translateDatFieldMysqlData */
    public function testTranslateDatFieldMysql(string $datfield, bool $unquote, string $expected): void
    {
        $driver = $this->getDriver('Lqdt\OrmJson\Database\Driver\DatFieldMysql');
        $result = $driver->translateDatField($datfield, $unquote);
        $this->assertEquals($expected, $result->sql(new ValueBinder()));
    }

    /** @dataProvider translateDatFieldMysqlData */
    public function testTranslateDatFieldMysqlAsSql(string $datfield, bool $unquote, string $expected): void
    {
        $driver = $this->getDriver('Lqdt\OrmJson\Database\Driver\DatFieldMysql');
        $result = $driver->translateDatFieldAsSql($datfield, $unquote);
        $this->assertEquals($expected, $result);
    }
}
