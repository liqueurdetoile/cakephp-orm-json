<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Driver;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Lqdt\OrmJson\Database\DatFieldDriverInterface;
use Lqdt\OrmJson\Database\Expression\DatFieldExpression;
use Lqdt\OrmJson\Database\JsonTypeMap;
use Lqdt\OrmJson\DatField\Exception\UnparsableDatFieldException;

class DatFieldMysql extends Mysql implements DatFieldDriverInterface
{
    use DatFieldSqlDialectTrait;

    /**
     * @inheritDoc
     */
    public function translateDatField($datfield, bool $unquote = false, $repository = null)
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

            ['doc' => $doc, 'path' => $path] = $this->_extractJsonParts($datfield, $repository);

            $expr = new FunctionExpression('JSON_EXTRACT', [$doc => 'identifier', $path => 'literal']);

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
    public function translateSetDatField(
        ComparisonExpression $expr,
        Query $query,
        JsonTypeMap $map
    ): ComparisonExpression {
        $datfield = $expr->getField();

        if (!is_string($datfield)) {
            return $expr;
        }

        if (!$this->isDatField($datfield)) {
            // We still need to apply JSON types if field is a JSON field
            $casters = $map->getCasters($query);
            $row = [$datfield => $expr->getValue()];
            $row = $this->_castRow($row, $casters, $query);
            $expr->setValue($row[$datfield]);

            return $expr;
        }

        $field = $this->getDatFieldPart('field', $datfield);
        ['doc' => $doc, 'path' => $path] = $this->_extractJsonParts($datfield);
        $caster = $map->getCaster($datfield, $query);
        $value = $this->_castValue($expr->getValue(), $query, $caster);
        $expr->setField($field);
        $expr->setValue(new FunctionExpression('JSON_SET', [$doc => 'identifier', $path => 'literal', $value]));

        return $expr;
    }

    /**
     * Utility function to parse needed parts for JSON_EXTRACT or JSON_SET functions
     *
     * @param  string $datfield Datfield
     * @param  string|null|false $repository Repository. IF set to false, existing model will be removed
     * @return array            Parts as doc and path
     */
    protected function _extractJsonParts(string $datfield, $repository = null): array
    {
        ['model' => $model, 'field' => $field, 'path' => $path] =
          $this->parseDatField($datfield, $repository ? $repository : null);
        $field = $repository !== false && $model ? implode('.', [$model, $field]) : $field;
        // Avoid adding a starting dot in path if querying an array or using joker
        $path = in_array($path[0], ['[', '*']) ? '$' . $path : implode('.', ['$', $path]);
        $path = "'" . $path . "'";

        return [
          'doc' => $field,
          'path' => $path,
        ];
    }
}
