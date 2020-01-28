<?php
namespace Lqdt\OrmJson\Database\Dialect;

use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Query;
use Lqdt\OrmJson\Utility\DatField;

trait DatFieldMysqlJoinTrait
{
    protected function _joinedFieldsConverter(array $joints, Query $query)
    {
        $alias = $query->getRepository()->getAlias();

        foreach ($joints as $joint) {
            $joint['conditions']->traverse(function ($expr) use ($alias, $query) {
                if ($expr instanceof IdentifierExpression) {
                    $field = $expr->getIdentifier();
                    if (DatField::isDatField($field)) {
                        // Fetch model from field value
                        $parts = explode('.', $field);
                        $model = array_shift($parts);
                        $field = implode('.', $parts);
                        $field = DatField::jsonFieldName($field, false, $model);
                        $expr->setIdentifier($field);
                    }
                }

                if ($expr instanceof Comparison) {
                    $field = $expr->getField();
                    $expr->setField(DatField::jsonFieldName($field, false, $alias));
                }
            });
        }
    }
}
