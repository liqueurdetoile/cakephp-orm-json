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
     * Parse where expressions and update JSON fields
     *
     * @version 2.0.0
     * @since   2.0.0
     * @param   string|ExpressionInterface      $expression Incoming expression
     */
    protected function _filtersConverter($expression, Query $query)
    {
        // Weird case though always possible
        if (is_string($expression)) {
            return $this->_rawSqlConverter($expression, $query);
        }

        // Catch SQL fragments at top level
        if ($expression instanceof QueryExpression) {
            $this->_queryExpressionConverter($expression, $query);
        }

        // Process inner expressions
        $expression->traverse(function ($expr) use ($query) {
            if ($expr instanceof Comparison) {
                $this->_comparisonConverter($expr, $query);
            }

            if ($expr instanceof QueryExpression) {
                $this->_queryExpressionConverter($expr, $query);
            }
        });
    }

    /**
     * Returns a new QueryExpression built upon the parsing of the expression to
     * update datfield names
     * @version 1.0.0
     * @since   1.5.0
     * @param   string          $expression Raw expression to transform
     * @return  QueryExpression             QueryExpression
     */
    protected function _rawSqlConverter(string $expression, Query $query)
    {
        return DatField::jsonFieldsNameinString($expression);
    }

    /**
     * Iterates over a QueryExpression to parse datfields in SQL fragments
     * Expressions are left unchanged as they'll be processed later when traversing the expression
     *
     * @version 1.0.0
     * @since   1.5.0
     * @param   QueryExpression $expression QueryExpression
     * @return  QueryExpression             Updated QueryExpression
     */
    protected function _queryExpressionConverter(QueryExpression $expression, Query $query)
    {
        $expression->iterateParts(function ($condition, $key) use ($query) {
            if (is_string($condition)) {
                return $this->_rawSqlConverter($condition, $query);
            }

            return $condition;
        });
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

        if ($field instanceof ExpressionInterface) {
            return;
        }

        if (DatField::isDatField($field)) {
            $field = DatField::jsonFieldName($field, false, $query->getRepository()->getAlias());
            $operator = $expression->getOperator();
            $value = $expression->getValue();

            if (is_null($value)) {
                $this->_convertNullData($expression, $field, $operator, $value, $query);
                return;
            }

            switch (gettype($value)) {
              case 'boolean':
                $this->_convertBooleanData($expression, $field, $operator, $value, $query);
                return;

              case 'string':
                $this->_convertStringData($expression, $field, $operator, $value, $query);
                return;

              case 'integer':
                $this->_convertIntegerData($expression, $field, $operator, $value, $query);
                return;

              case 'double':
                $this->_convertDoubleData($expression, $field, $operator, $value, $query);
                return;

              case 'array':
                return $this->_convertArrayData($expression, $field, $operator, $value, $query);
              default:
                throw new Exception('Unsupported type for value : ' . gettype($value));
              break;
            }
        }

        return;
    }

    protected function _convertNullData($expression, $field, $operator, $value, $query)
    {
        $expression->setField($field);
        $expression->setOperator('=');
        $expression->setValue($query->newExpr("CAST('null' AS JSON)"));
    }

    protected function _convertBooleanData($expression, $field, $operator, $value, $query)
    {
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

        $expression->setField($field);
        $expression->setOperator($cleanoperator);
        $expression->setValue($query->newExpr($value ? 'true': 'false'));
    }

    protected function _convertStringData($expression, $field, $operator, $value)
    {
        $operator = strtolower($operator);

        // LIKE and NOT LIKE comparison statements must be surrounded by "" to work
        if ($operator === 'like' || $operator === 'not like') {
            $expression->setField("CAST($field AS CHAR)"); // Need this to perform a case insensitice comparison
            $expression->setValue('"' . $value . '"');
        } else {
            $expression->setField($field);
        }
    }

    protected function _convertIntegerData($expression, $field, $operator, $value, $query)
    {
        $expression->setField($field);
        $expression->setValue($query->newExpr((string) $value));
    }

    /**
     * PDO statement are failing on float values and PDO::PARAM_STR is not valid choice
     * to allow Mysql operations on float values within JSON fields
     * @version [version]
     * @since   [since]
     * @param   [type]    $expression [description]
     * @param   [type]    $field      [description]
     * @param   [type]    $operator   [description]
     * @param   [type]    $value      [description]
     * @return  [type]                [description]
     */
    protected function _convertDoubleData($expression, $field, $operator, $value, $query)
    {
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

        $expression->setField($field);
        $expression->setOperator($cleanoperator);
        $expression->setValue($query->newExpr((string) $value));
    }

    protected function _convertArrayData($expression, $field, $operator, $value, $query)
    {
        $generator = $query->getValueBinder();

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
            $value = array_values($value);
            $expression->setField("JSON_CONTAINS(CAST('" . json_encode($value) . "' AS JSON), " . $field . ")");
            $expression->setOperator('=');
            $expression->setValue($query->newExpr('1'));
            return;
          default:
            throw new Exception('Unsupported operator ' . $operator . ' with OBJECT/ARRAY data type');
        }

        $expression->setField($field);
        $expression->setOperator($cleanoperator);
        $expression->setValue($query->newExpr("CAST('" . json_encode($value) . "' AS JSON)"));
    }
}
