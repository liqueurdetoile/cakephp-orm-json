<?php
namespace Lqdt\OrmJson\Database\Driver;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\Comparison;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlJoinTrait;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlOrderTrait;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlSelectTrait;
use Lqdt\OrmJson\Database\Dialect\DatFieldMysqlWhereTrait;

class DatFieldMysql extends Mysql
{
    use DatFieldMysqlJoinTrait;
    use DatFieldMysqlOrderTrait;
    use DatFieldMysqlSelectTrait;
    use DatFieldMysqlWhereTrait;

    public function queryTranslator($type)
    {
        return function ($query) use ($type) {
            $repository = $query->getRepository();

            if ($repository->hasBehavior('DatField') || $repository->hasBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior')) {
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
                    $this->_joinedFieldsConverter($joints, $query);
                }

                // Process filters
                $where = $query->clause('where');
                if (!empty($where)) {
                    // $query->where($this->_filtersConverter($where, $query), [], true);
                    $this->_filtersConverter($where, $query);
                }
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
