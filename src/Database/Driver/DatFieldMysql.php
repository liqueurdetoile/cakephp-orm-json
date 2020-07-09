<?php
namespace Lqdt\OrmJson\Database\Driver;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\Comparison;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlExpressionConverterTrait;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlOrderTrait;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlSelectTrait;

class DatFieldMysql extends Mysql
{
    use DatFieldMysqlExpressionConverterTrait;
    use DatFieldMysqlOrderTrait;
    use DatFieldMysqlSelectTrait;

    public function queryTranslator($type)
    {
        return function ($query) use ($type) {
            try {
                $repository = $query->getRepository();

                if ($repository->hasBehavior('Datfield') || $repository->hasBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior')) {
                    // Process order
                    $order = $query->clause('order');
                    if (!empty($order)) {
                        $this->_orderedFieldsConverter($order, $query);
                    }

                    // Process select
                    $select = $query->clause('select');
                    if (!empty($select)) {
                        $query->select($this->_selectedFieldsConverter($select, $query), true);
                    }

                    // Process joins
                    $joints = $query->clause('join');
                    if (!empty($joints)) {
                        foreach ($joints as $joint) {
                            $joint['conditions']->traverse(function ($e) use ($query) {
                                $this->convertExpression($e, $query);
                            });
                        }
                    }

                    // Process filters
                    $where = $query->clause('where');
                    if (!empty($where)) {
                        $this->convertExpression($where, $query);
                    }
                }
            } catch (\Error $err) {
            }

            // Apply parent driver translator transformations
            $parentTranslator = parent::queryTranslator($type);
            $query = $parentTranslator($query);

            return $query;
        };
    }

    public function prepare($query)
    {
        if ($query instanceof \Cake\ORM\Query) {
            // debug($query);
            // debug($query->sql());
        }
        return parent::prepare($query);
    }

    /**
     * We need to override this method from SqlDialectTrait as it breaks apart datfields with delete
     * @inheritDoc
     */
    protected function _removeAliasesFromConditions($query)
    {
        if ($query->clause('join')) {
            throw new \RuntimeException(
                'Aliases are being removed from conditions for UPDATE/DELETE queries, ' .
                'this can break references to joined tables.'
            );
        }

        $conditions = $query->clause('where');
        if ($conditions) {
            $conditions->traverse(function ($condition) {
                if (!($condition instanceof Comparison)) {
                    return $condition;
                }

                $field = $condition->getField();
                if ($field instanceof ExpressionInterface || strpos($field, '.') === false) {
                    return $condition;
                }

                $parts = explode('.', $field);
                array_shift($parts);
                // list(, $field) = explode('.', $field);
                $condition->setField(implode('.', $parts));

                return $condition;
            });
        }

        return $query;
    }
}
