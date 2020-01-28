<?php
namespace Lqdt\OrmJson\Database\Dialect;

use Cake\Core\Exception\Exception;
use Cake\Database\ExpressionInterface;
use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\Query;
use Lqdt\OrmJson\Utility\DatField;

trait DatFieldMysqlWhereTrait
{
    /**
     * Returns the appropriate ExpressionInterface regarding the incoming one
     *
     * @version 1.0.0
     * @since   1.5.0
     * @param   string|ExpressionInterface      $expression Incoming expression
     */
    protected function _filtersConverter($expression, Query $query)
    {
        if (is_string($expression)) {
            return $this->_rawSqlConverter($expression, $query);
        }

        if ($expression instanceof QueryExpression) {
            return $this->_queryExpressionConverter($expression, $query);
        }

        if ($expression instanceof Comparison) {
            return $this->_comparisonConverter($expression, $query);
        }

        if ($expression instanceof UnaryExpression) {
            return $this->_unaryExpressionConverter($expression, $query);
        }

        throw new Exception('Unmanaged expression');
    }

    /**
     * Returns a new QueryExpression built upon the parsing of the expression to
     * update datfield names
     * @version 1.0.0
     * @since   1.5.0
     * @param   string          $expression Raw expression to transform
     * @return  QueryExpression             QueryExpression
     */
    protected function _rawSqlConverter(string $expression, Query $query) : QueryExpression
    {
        return $query->newExpr(DatField::jsonFieldsNameinString($expression));
    }

    /**
     * Update or replace the Comparison expression to perform comparisons on
     * datFields. In some cases, PDO limitations implies to replace the
     * expression with a raw SQL fragment. It can be a bit dangerous when
     * using raw user input to perform global matching in `array` mode.
     *
     * Regular fields expressions are left as is.
     *
     * @version 1.0.4
     * @since   1.5.0
     * @param   Comparison $expression Comparison expression
     * @return  Comparison|QueryExpression   Updated expression
     */
    protected function _comparisonConverter(Comparison $expression, Query $query)
    {
        $field = $expression->getField();

        if (DatField::isDatField($field)) {
            $field = DatField::jsonFieldName($field, false, $query->getRepository()->getAlias());
            $operator = $expression->getOperator();
            $value = $expression->getValue();

            if (is_null($value)) {
                // No PDO way to handle null
                return $query->newExpr($field . ' = ' . "CAST('null' AS JSON)");
            } else {
                switch (gettype($value)) {
                  case 'string':
                    // LIKE and NOT LIKE comparison statements must be surrounded by "" to work
                    if ($operator === 'like' || $operator === 'not like') {
                        $value = '"' . $value . '"';
                    }
                    $type = 'string';
                    break;

                  case 'integer':
                    $type = 'integer';
                    break;

                  case 'double':
                    // PDO statement are failing on float values and PDO::PARAM_STR is not valid choice
                    // to allow Mysql operations on float values within JSON fields
                    // We must rebuild a SQL fragment from original expression data

                    switch ($operator) {
                      case '=':
                      case '<':
                      case '>':
                      case '<=':
                      case '>=':
                      case '<>':
                        $cleanoperator = $operator;
                        break;
                      case 'eq':
                        $cleanoperator = '=';
                        break;
                      case 'notEq':
                        $cleanoperator = '<>';
                        break;
                      case 'lt':
                        $cleanoperator = '<';
                        break;
                      case 'lte':
                        $cleanoperator = '<=';
                        break;
                      case 'gte':
                        $cleanoperator = '>=';
                        break;
                      case 'gt':
                        $cleanoperator = '>';
                        break;
                      default:
                        throw new Exception('Unsupported operator ' . $operator . ' with DOUBLE data type');
                    }

                    return $query->newExpr($field . " $cleanoperator " . $value);

                  case 'boolean':
                    // PDO statement are also failing on boolean
                    // We must rebuild a SQL fragment from original expression data
                    switch ($operator) {
                      case '=':
                      case '!=':
                      case '<>':
                        $cleanoperator = $operator;
                        break;
                      case 'eq':
                        $cleanoperator = '=';
                        break;
                      case 'notEq':
                        $cleanoperator = '!=';
                        break;
                      default:
                        throw new Exception('Unsupported operator ' . $operator . ' with BOOLEAN data type');
                    }

                    return $query->newExpr($field . " $cleanoperator " . ($value ? 'true': 'false'));

                  case 'array':
                    // No PDO way to handle arrays and objects
                    switch ($operator) {
                      case '=':
                      case '<>':
                        $cleanoperator = $operator;
                        break;
                      case 'eq':
                        $cleanoperator = '=';
                        break;
                      case 'notEq':
                        $cleanoperator = '<>';
                        break;
                      case 'in':
                      case 'IN':
                        return $query->newExpr("JSON_CONTAINS(CAST('" . json_encode(array_values($value)) . "' AS JSON), " . $field . ")");
                      default:
                        throw new Exception('Unsupported operator ' . $operator . ' with OBJECT/ARRAY data type');
                    }

                    return $query->newExpr($field . " $cleanoperator " . "CAST('" . json_encode($value) . "' AS JSON)");

                  default:
                    throw new Exception('Unsupported type for value : ' . gettype($value));
                  break;
                }
            }

            return new Comparison($field, $value, $type, $operator);
        }

        return $expression;
    }

    /**
     * Parses the unary expression to apply conversions on childrens and returns
     * an updated UnaryExpression
     *
     * **Note** : This a **VERY** hacky way because the UnaryExpression class doesn't expose
     * getter/setter for protected `_value` property.
     *
     * In this implementation, it causes an infinite loop when used directly with a SQL fragment :
     *
     * `['NOT' => 'sub.prop@datfield like "%buggy%"]`
     *
     * That's why, an exception is thrown as soon as $value is extracted
     *
     * @version 1.0.1
     * @since   1.5.0
     * @param   UnaryExpression $expression Expression
     * @return  UnaryExpression             New expression
     */
    protected function _unaryExpressionConverter(UnaryExpression $expression, Query $query) : UnaryExpression
    {
        $value = null;
        try {
            $expression->traverse(function ($exp) use (&$value, $query) {
                $value = $this->_filtersConverter($exp, $query);
                throw new Exception();
            });
        } catch (Exception $err) {
            if (!empty($value)) {
                return new UnaryExpression('NOT', $value);
            } else {
                throw new Exception('Unable to extract value from UnaryExpression');
            }
        }
    }

    /**
     * Iterates over a QueryExpression and replace Comparison expressions
     * to handle JSON comparison in datfields.
     *
     * @version 1.0.0
     * @since   1.5.0
     * @param   QueryExpression $expression QueryExpression
     * @return  QueryExpression             Updated QueryExpression
     */
    protected function _queryExpressionConverter(QueryExpression $expression, Query $query) : QueryExpression
    {
        $expression->iterateParts(function ($condition, $key) use ($query) {
            return $this->_filtersConverter($condition, $query);
        });

        return $expression;
    }
}
