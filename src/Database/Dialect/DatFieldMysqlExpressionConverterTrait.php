<?php
namespace Lqdt\OrmJson\Database\Dialect;

use Cake\Core\Exception\Exception;
use Cake\Database\ExpressionInterface;
use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\Query;
use Lqdt\OrmJson\Utility\DatField;

trait DatFieldMysqlExpressionConverterTrait
{
    public function convertExpression($expression, Query $query)
    {
        if (is_string($expression)) {
            return $this->_rawSqlConverter($expression, $query);
        }

        if ($expression instanceof IdentifierExpression) {
            return $this->_identifierExpressionConverter($expression, $query);
        }

        if ($expression instanceof Comparison) {
            return $this->_comparisonConverter($expression, $query);
        }

        if ($expression instanceof QueryExpression) {
            return $this->_queryExpressionConverter($expression, $query);
        }

        if ($expression instanceof ExpressionInterface) {
            $expression->traverse(function ($e) use ($query) {
                $this->convertExpression($e, $query);
            });
        }

        return $expression;
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

    protected function _identifierExpressionConverter(IdentifierExpression $expression, Query $query)
    {
        $field = $expression->getIdentifier();

        if (DatField::isDatField($field)) {
            // Fetch model from field value
            $parts = explode('.', $field);
            $model = array_shift($parts);
            $field = implode('.', $parts);
            $field = DatField::jsonFieldName($field, false, $model);
            $expression->setIdentifier($field);
        }

        return $expression;
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
        $expression->iterateParts(function ($expr, $key) use ($query) {
            return $this->convertExpression($expr, $query);
        });

        return $expression;
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
            return $expression;
        }

        if (DatField::isDatField($field)) {
            $field = DatField::jsonFieldName($field, false, $query->getRepository()->getAlias());
            $operator = $expression->getOperator();
            $value = $expression->getValue();

            if ($value instanceof ExpressionInterface) {
                $expression->setField($field);
                $expression->setValue($this->convertExpression($value, $query));

                return $expression;
            }

            if (is_null($value)) {
                return $this->_convertNullData($expression, $field, $operator, $value, $query);
            }

            switch (gettype($value)) {
              case 'boolean':
                return $this->_convertBooleanData($expression, $field, $operator, $value, $query);

              case 'string':
                return $this->_convertStringData($expression, $field, $operator, $value, $query);

              case 'integer':
                return $this->_convertIntegerData($expression, $field, $operator, $value, $query);

              case 'double':
                return $this->_convertDoubleData($expression, $field, $operator, $value, $query);

              case 'array':
                return $this->_convertArrayData($expression, $field, $operator, $value, $query);

              default:
                throw new Exception('Unsupported type for value : ' . gettype($value));
            }
        }

        return $expression;
    }

    protected function _convertNullData($expression, $field, $operator, $value, $query)
    {
        $expression->setField($field);
        $expression->setOperator('=');
        $expression->setValue($query->newExpr("CAST('null' AS JSON)"));

        return $expression;
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

        return $expression;
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

        return $expression;
    }

    protected function _convertIntegerData($expression, $field, $operator, $value, $query)
    {
        $expression->setField($field);
        $expression->setValue($query->newExpr((string) $value));

        return $expression;
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

        return $expression;
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

            return new Comparison("JSON_CONTAINS(CAST('" . json_encode($value) . "' AS JSON), " . $field . ")", 1, 'integer', '=');
          default:
            throw new Exception('Unsupported operator ' . $operator . ' with OBJECT/ARRAY data type');
        }

        $expression->setField($field);
        $expression->setOperator($cleanoperator);
        $expression->setValue($query->newExpr("CAST('" . json_encode($value) . "' AS JSON)"));

        return $expression;
    }
}
