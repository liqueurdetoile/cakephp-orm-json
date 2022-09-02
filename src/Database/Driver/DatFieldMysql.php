<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Driver;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Lqdt\OrmJson\Database\DatFieldDriverInterface;
use Lqdt\OrmJson\Database\Expression\DatFieldExpression;
use Lqdt\OrmJson\DatField\Exception\UnparsableDatFieldException;

class DatFieldMysql extends Mysql implements DatFieldDriverInterface
{
    use DatFieldSqlDialectTrait;

    /**
     * @inheritDoc
     */
    public function translateDatField($datfield, bool $unquote = false, ?string $repository = null)
    {
        try {
            if (is_array($datfield)) {
                return array_map(function ($field) use ($unquote, $repository) {
                    return $this->translateDatField($field, $unquote, $repository);
                }, $datfield);
            }

            if ($datfield instanceof DatFieldExpression) {
                return $datfield;
            }

            if ($datfield instanceof ExpressionInterface) {
                return $datfield;
            }

            ['model' => $model, 'field' => $field, 'path' => $path] = $this->parseDatField($datfield, $repository);
            $field = $model ? implode('.', [$model, $field]) : $field;
          // Avoid adding a starting dot in path if querying an array or using joker
            $path = in_array($path[0], ['[', '*']) ? '$' . $path : implode('.', ['$', $path]);
            $path = "'" . $path . "'";

            $expr = new FunctionExpression('JSON_EXTRACT', [$field => 'identifier', $path => 'literal']);

            $expr = $unquote ? new FunctionExpression('JSON_UNQUOTE', [$expr]) : $expr;
            $expr = new DatFieldExpression([$expr]);

            return $expr->setDatField($datfield);
        } catch (UnparsableDatFieldException $err) {
            return $datfield;
        }
    }

    /**
     * @inheritDoc
     */
    public function translateDatFieldAsSql($datfield, bool $unquote = false, ?string $repository = null): string
    {
        try {
            if ($datfield instanceof DatFieldExpression) {
                return $datfield->sql(new ValueBinder());
            }

            ['model' => $model, 'field' => $field, 'path' => $path] = $this->parseDatField($datfield, $repository);
            $field = $model ? implode('.', [$model, $field]) : $field;
          // Avoid adding a starting dot in path if querying an array or using joker
            $path = in_array($path[0], ['[', '*']) ? '$' . $path : implode('.', ['$', $path]);
            $path = "'" . $path . "'";

            $expr = new FunctionExpression('JSON_EXTRACT', [$field => 'identifier', $path => 'literal']);

            $expr = $unquote ? new FunctionExpression('JSON_UNQUOTE', [$expr]) : $expr;
            $expr = new DatFieldExpression([$expr]);

            return $expr->sql(new ValueBinder());
        } catch (UnparsableDatFieldException $err) {
            if (is_string($datfield)) {
                return $datfield;
            }

            throw $err;
        }
    }
}
