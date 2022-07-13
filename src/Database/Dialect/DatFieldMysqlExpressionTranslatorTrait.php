<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Dialect;

use Cake\Core\Exception\Exception;
use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\FieldInterface;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;

trait DatFieldMysqlExpressionTranslatorTrait
{
    /**
     * Applies translator to any Expression
     *
     * @param string|\Cake\Database\ExpressionInterface $expression Literal or object expression
     * @param \Cake\Database\Query $query       Query
     * @return mixed Updated expression
     */
    public function translateExpression($expression, Query $query)
    {
        if (is_string($expression)) {
            return $this->_translateRawSQL($expression, $query);
        }

        if (
            $expression instanceof Comparison
            || $expression instanceof ComparisonExpression
        ) {
            return $this->_translateComparisonExpression($expression, $query);
        }

        if ($expression instanceof IdentifierExpression) {
            return $this->_translateIdentifierExpression($expression, $query);
        }

        if ($expression instanceof QueryExpression) {
            return $this->_translateQueryExpression($expression, $query);
        }

        if ($expression instanceof UnaryExpression) {
            return $this->_translateUnaryExpression($expression, $query);
        }

        if ($expression instanceof FieldInterface) {
            $field = $expression->getField();
            if ($this->isDatField($field)) {
                $expression->setField($this->translateToJsonExtract($field, true, $query->getRepository()->getAlias()));
            }
        }

        // Parses content of anything else that is an expression
        if ($expression instanceof ExpressionInterface) {
            $expression->traverse(
                function ($e) use ($query) {
                    $this->translateExpression($e, $query);
                }
            );
        }

        return $expression;
    }

    /**
     * Process a same type ComparisonExpression by extracting unquoted
     *
     * @param \Cake\Database\Expression\Comparison|\Cake\Database\Expression\ComparisonExpression $expression ComparisonExpression expression
     * @param \Cake\Database\Query $query Query
     * @return \Cake\Database\Expression\Comparison|\Cake\Database\Expression\ComparisonExpression Updated expression
     */
    protected function _compareTo(
        $expression,
        Query $query
    ) {
        $datfield = $expression->getField();
        $datfield = $this->translateToJsonExtract($datfield, true, $query->getRepository()->getAlias());
        $expression->setField($datfield);

        return $expression;
    }

    /**
     * Process a ComparisonExpression expression for boolean values
     *
     * @param \Cake\Database\Expression\Comparison|\Cake\Database\Expression\ComparisonExpression $expression ComparisonExpression expression
     * @param \Cake\Database\Query $query Query
     * @return \Cake\Database\Expression\Comparison|\Cake\Database\Expression\ComparisonExpression Updated expression
     */
    protected function _compareToBoolean(
        ComparisonExpression $expression,
        Query $query
    ): ComparisonExpression {
        $datfield = $expression->getField();
        $datfield = $this->translateToJsonExtract($datfield, false, $query->getRepository()->getAlias());
        $value = $expression->getValue();
        $operator = $expression->getOperator();

        switch ($operator) {
            case '=':
            case '<>':
            case '!=':
                break;
            case 'eq':
                $operator = '=';
                break;
            case 'notEq':
                $operator = '<>';
                break;
            default:
                throw new Exception('Unsupported operator ' . $operator . ' with BOOLEAN data type');
        }

        $expression->setField($datfield);
        $expression->setOperator($operator);
        $expression->setValue($query->newExpr($value ? 'true' : 'false'));

        return $expression;
    }

    /**
     * Processes cases where CakePHP does not complain about using null within Comparison (prior 4.1)
     *
     * @param \Cake\Database\Expression\Comparison|\Cake\Database\Expression\ComparisonExpression $expression Expression
     * @param \Cake\Database\Query $query Query
     * @return string|\Cake\Database\Expression\IdentifierExpression SQL fragment or query expression
     */
    protected function _compareToNull($expression, Query $query)
    {
        $datfield = $expression->getField();
        $operator = $expression->getOperator();
        $not = false;
        if (in_array(strtolower($operator), ['<>', '!=', 'notEq'])) {
            $not = true;
        }

        return $this->_translateNull($datefield, $query, $not);
    }

    /**
     * Update or replace the ComparisonExpression expression to perform ComparisonExpressions on
     * datFields. In some cases, PDO limitations implies to replace the
     * expression with a raw SQL fragment. It can be a dangerous when
     * using raw user input to perform global matching in `array` mode.
     *
     * Regular fields expressions are left as is.
     *
     * @version 1.0.4
     * @since   1.5.0
     * @param \Cake\Database\Expression\Comparison|\Cake\Database\Expression\ComparisonExpression $expression ComparisonExpression expression
     * @param \Cake\Database\Query $query Query
     * @return \Cake\Database\Expression\ComparisonExpression|\Cake\Database\Expression\QueryExpression Updated expression
     */
    protected function _translateComparisonExpression($expression, Query $query)
    {
        $field = $expression->getField();

        // If field is an expression, simply returns as it will be processed later on when traversing QueryExpression
        if ($field instanceof ExpressionInterface) {
            return $expression;
        }

        // Checks if it's a datfield
        if ($this->isDatField($field)) {
            $value = $expression->getValue();

            // Some older versions of CakePHP will not complain when using [datfield => null]
            if ($value === null) {
                return $this->_compareAsNull($field, $query);
            }

            if ($value instanceof ExpressionInterface) {
                $field = $this->translateToJsonExtract($field, false, $query->getRepository()->getAlias());
                $expression->setField($field);
                $expression->setValue($this->translateExpression($value, $query));

                return $expression;
            }

            // Special case where we need to parse date as DATE to enable comparison
            if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
                $repository = $query->getRepository();
                $field = $this->getDatFieldPart('field', $field, $repository->getAlias());
                $format = $repository->getJsonFieldConfig($field, $query->getOptions(), 'jsonDateTimeTemplate');
                $expression->setValue($value->format($format));
            }

            return is_bool($value) ?
              $this->_compareToBoolean($expression, $query) :
              $this->_compareTo($expression, $query);
        }

        return $expression;
    }

    /**
     * Translates an IdentifierExpression
     *
     * @param \Cake\Database\Expression\IdentifierExpression $expression Expression
     * @param \Cake\Database\Query $query If `true returns indentifier instead of ipdated expression
     * @return string|\Cake\Database\Expression\IdentifierExpression SQL fragment or query expression
     */
    protected function _translateIdentifierExpression(
        IdentifierExpression $expression,
        Query $query
    ) {
        $field = $expression->getIdentifier();

        if ($this->isDatField($field)) {
            $field = $this->translateToJsonExtract($field, false, $query->getRepository()->getAlias());
            $expression->setIdentifier($field);
        }

        return $expression;
    }

    /**
     * Returns an expression suitable for evaluating missing or null values within a JSON field
     *
     * When evaluating IS NULL, rows with missing targetted key will also be returned
     *
     * @param  string               $datfield    Dat field
     * @param \Cake\Database\Query $query Query
     * @param bool $not IS NOT NULL case if `true`
     * @return \Cake\Database\Expression\ComparisonExpression|\Cake\Database\Expression\QueryExpression Replaced expression
     */
    protected function _translateNull(string $datfield, Query $query, $not = false)
    {
        $field = $this->translateToJsonExtract($datfield, false, $query->getRepository()->getAlias());

        // Condition for existing keys
        $operator = $not ? '<>' : '=';
        $cmp = $query->newExpr("{$field} {$operator} CAST('null' AS JSON)");

        if ($not) {
            return $cmp;
        }

        // Condition for missing key (only for IS NULL)
        $cmp2 = $query->newExpr(
            $this->translateToJsonContainsPath($datfield, $query->getRepository()->getAlias()) . ' = 0'
        );

        return new QueryExpression([$cmp, $cmp2], [], 'OR');
    }

    /**
     * Iterates over a QueryExpression and convert content
     *
     * @version 1.0.0
     * @since   1.5.0
     * @param \Cake\Database\Expression\QueryExpression $expression QueryExpression
     * @param \Cake\Database\Query $query Query
     * @return \Cake\Database\Expression\QueryExpression Updated QueryExpression
     */
    protected function _translateQueryExpression(QueryExpression $expression, Query $query): QueryExpression
    {
        $expression->iterateParts(
            function ($expr) use ($query) {
                return $this->translateExpression($expr, $query);
            }
        );

        return $expression;
    }

    /**
     * Parsed DatField in SQL fragments
     *
     * @param  string   $fragment   SQL fragment to parse
     * @param \Cake\Database\Query $query Query
     * @return string   Updated fragment
     */
    protected function _translateRawSQL(string $fragment, Query $query): string
    {
        return $this->translateSQLToJsonExtract($fragment, true, $query->getRepository()->getAlias());
    }

    /**
     * Converts and unary expression
     *
     * It's quite hacky as class doesn't expose getters and setters for `value` and `operator`
     *
     * It is used to parse 'IS NULL' or 'IS NOT NULL' statements
     *
     * @param \Cake\Database\Expression\UnaryExpression $expression Expression
     * @param \Cake\Database\Query $query Query
     * @return \Cake\Database\Expression\UnaryExpression|\Cake\Database\Expression\ComparisonExpression Updated expression
     */
    protected function _translateUnaryExpression(UnaryExpression $expression, Query $query)
    {
        $reflection = new \ReflectionClass($expression);
        $operator = $reflection->getProperty('_operator');
        $operator->setAccessible(true);
        $op = $operator->getValue($expression);
        $value = $reflection->getProperty('_value');
        $value->setAccessible(true);
        $datfield = $value->getValue($expression);

        // IS NULL / IS NOT NULL cases
        if ($op === 'IS NULL' || $op === 'IS NOT NULL') {
            if ($datfield instanceof IdentifierExpression) {
                $datfield = $datfield->getIdentifier();
            }

            return $this->_translateNull($datfield, $query, $op === 'IS NOT NULL');
        }

        // Simply process value in other cases
        $value->setValue($value, $this->translateExpression($datfield, $query));

        return $expression;
    }
}
