<?php
namespace Lqdt\OrmJson\Database\Dialect;

// use Cake\Core\Exception\Exception;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Query;
use Cake\ORM\Entity;
use Lqdt\OrmJson\Utility\DatField;

trait DatFieldMysqlOrderTrait
{
    protected function _removeOrderingFields($row, array $aliases)
    {
        foreach ($aliases as $alias) {
            if ($row instanceof Entity) {
                $row->unsetProperty($alias);
            } else {
                unset($row[$alias]);
            }
        }

        return $row;
    }

    protected function _registerDatFieldForSorting(string $datfield, string $model, array &$aliases, Query $query) : string
    {
        $field = DatField::jsonFieldName($datfield, false, $model);
        $alias = DatField::buildAliasFromTemplate($datfield, '{{model}}__sorting__{{field}}_{{path}}', '_', $model);
        $aliases[] = DatField::buildAliasFromTemplate($datfield, 'sorting__{{field}}_{{path}}', '_', $model);
        $query->select([$alias => $field]);
        $types = $query->getSelectTypeMap()->getTypes();
        $types[$alias] = 'json';
        $query->getSelectTypeMap()->setTypes($types);

        return $alias;
    }

    protected function _orderedFieldsConverter(OrderByExpression $order, Query $query) : OrderByExpression
    {
        $model = $query->getRepository()->getAlias();
        $aliases = [];
        $updated = false;
        $autofields = $query->isAutoFieldsEnabled();

        $order->iterateParts(function ($direction, &$key) use ($model, &$aliases, $query, &$updated) {
            if (DatField::isDatField($direction)) {
                // Add field to select
                $updated = true;
                return $this->_registerDatFieldForSorting($direction, $model, $aliases, $query);
            }

            if (DatField::isDatField($key)) {
                // Add field to select
                $updated = true;
                $key = $this->_registerDatFieldForSorting($key, $model, $aliases, $query);
            }

            return $direction;
        });

        if ($updated) {
            // restore autofields if needeed
            $query->enableAutoFields($autofields);
            $query->mapReduce(function ($row, $key, $mapReduce) use ($aliases) {
                $mapReduce->emit($this->_removeOrderingFields($row, $aliases));
            });
        }

        return $order;
    }
}
