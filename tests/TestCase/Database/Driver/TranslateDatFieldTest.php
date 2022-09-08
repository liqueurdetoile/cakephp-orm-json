<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\Database\Driver;

use Cake\Database\ValueBinder;

/**
 * App\Model\Behavior\JsonBehavior Test Case
 */
class TranslateDatFieldTest extends TestCase
{
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
        /** @var \Lqdt\OrmJson\Database\DatFieldDriverInterface $driver */
        $driver = $this->connection->getDriver();
        /** @var \Lqdt\OrmJson\Database\Expression\DatFieldExpression $result */
        $result = $driver->translateDatField($datfield, $unquote);
        $this->assertEquals($expected, $result->sql(new ValueBinder()));
    }
}
