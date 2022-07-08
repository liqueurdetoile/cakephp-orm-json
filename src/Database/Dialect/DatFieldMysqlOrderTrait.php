<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Dialect;

use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Query;

trait DatFieldMysqlOrderTrait
{
    /**
     * Updates fields in order clause by JSON_EXTRACT equivalent
     *
     * @param \Cake\Database\Expression\OrderByExpression $order Order expression
     * @param \Cake\Database\Query $query Query
     * @return \Cake\Database\Expression\OrderByExpression Updated Order expression
     */
    protected function translateOrderBy(OrderByExpression $order, Query $query): OrderByExpression
    {
        $model = $query->getRepository()->getAlias();

        $order->iterateParts(function ($fieldOrOrder, &$key) use ($model) {
            if ($this->isDatField($fieldOrOrder)) {
                return $this->translateToJsonExtract($fieldOrOrder, false, $model);
            }

            if ($this->isDatField($key)) {
                $key = $this->translateToJsonExtract($key, false, $model);
            }

            return $fieldOrOrder;
        });

        return $order;
    }
}
