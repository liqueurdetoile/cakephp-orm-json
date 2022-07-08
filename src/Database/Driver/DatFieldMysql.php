<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Driver;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\Comparison;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlExpressionTranslatorTrait;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlOrderTrait;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlSelectTrait;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

class DatFieldMysql extends Mysql
{
    use DatFieldMysqlExpressionTranslatorTrait;
    use DatFieldMysqlOrderTrait;
    use DatFieldMysqlSelectTrait;
    use DatFieldAwareTrait;

    /**
     * @inheritDoc
     */
    public function queryTranslator(string $type): \Closure
    {
        return function ($query) use ($type) {
            try {
                $repository = $query->getRepository();

                if (
                    $repository->hasBehavior('Datfield')
                    || $repository->hasBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior')
                ) {
                    // Process select
                    $select = $query->clause('select');
                    if (!empty($select)) {
                        $query->select($this->translateSelect($select, $query), true);
                    }

                    // Process order
                    $order = $query->clause('order');
                    if (!empty($order)) {
                        $this->translateOrderBy($order, $query);
                    }

                    // Process joins
                    $joints = $query->clause('join');
                    if (!empty($joints)) {
                        foreach ($joints as $joint) {
                            $joint['conditions']->traverse(
                                function ($e) use ($query) {
                                    $this->translateExpression($e, $query);
                                }
                            );
                        }
                    }

                    // Process filters
                    $where = $query->clause('where');
                    if (!empty($where)) {
                        $this->translateExpression($where, $query);
                    }

                    // Process group
                    $group = [];
                    foreach ($query->clause('group') as $field) {
                        $group[] = $this->translateExpression($field, $query);
                    }
                    $query->group($group, true);

                    // Process having
                    $having = $query->clause('having');
                    if (!empty($having)) {
                        $this->translateExpression($having, $query);
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

    /**
     * @inheritDoc
     */
    protected function _removeAliasesFromConditions(Query $query): Query
    {
        if ($query->clause('join')) {
            throw new \RuntimeException(
                'Aliases are being removed from conditions for UPDATE/DELETE queries, ' .
                'this can break references to joined tables.'
            );
        }

        $conditions = $query->clause('where');
        if ($conditions) {
            $conditions->traverse(
                function ($condition) {
                    if (!($condition instanceof Comparison)) {
                        return $condition;
                    }

                    $field = $condition->getField();
                    if (is_array($field) || $field instanceof ExpressionInterface || strpos($field, '.') === false) {
                        return $condition;
                    }

                    $parts = explode('.', $field);
                    array_shift($parts);
                    // Override is required here as native function is breaking datfield notation
                    // list(, $field) = explode('.', $field);
                    $condition->setField(implode('.', $parts));

                    return $condition;
                }
            );
        }

        return $query;
    }
}
