<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Driver;

use Cake\Database\Expression\BetweenExpression;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\OrderClauseExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Log\Log;
use Cake\ORM\Query as ORMQuery;
use Lqdt\OrmJson\Database\Expression\DatFieldExpression;
use Lqdt\OrmJson\Database\JsonTypeMap;
use Lqdt\OrmJson\DatField\DatFieldParserTrait;
use Lqdt\OrmJson\DatField\Exception\MissingPathInDataDatFieldException;

trait DatFieldSqlDialectTrait
{
    use DatFieldParserTrait {
        DatFieldParserTrait::isDatField as protected _isDatFieldString;
    }

    /** @inheritDoc */
    public function isDatField($datfield): int
    {
        if (is_string($datfield)) {
            return $this->_isDatFieldString($datfield);
        }

        if ($datfield instanceof DatFieldExpression) {
            return 3;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function quoteIdentifier($identifier): string
    {
        $identifier = trim($identifier);

        // Handles -> and ->> operators available in Mysql and PostgreSql
        if (preg_match('/^([\.\w-]+)(\s?)->(.*)$/', $identifier, $matches)) {
            return $this->quoteIdentifier($matches[1]) . $matches[2] . '->' . $matches[3];
        }

        // Overrides function detection as it does not supports multiple comma separated arguments
        // It must be handled before comma detector
        if (preg_match('/^([\w-]+)\((.*)\)(\s*AS\s*([\w-]+))?$/', $identifier, $matches)) {
            return empty($matches[4]) ?
              $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ')' :
              $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ') AS ' . $this->quoteIdentifier($matches[4]);
        }

        // Handles comma separated multiple arguments, usually in functions
        if (strpos($identifier, ',') !== false) {
            $arguments = array_map(function ($arg) {
                return $this->quoteIdentifier($arg);
            }, explode(',', $identifier));

            return implode(', ', $arguments);
        }

        return parent::QuoteIdentifier($identifier);
    }

    /**
     * @inheritDoc
     */
    public function queryTranslator($type): \Closure
    {
        // We must preprocess translation in order to let other translations be done with translated datfield
        return function (Query $query) use ($type) {
            // Checks that datfield are enabled in this query
            $datFieldsEnabled = $this->_getQueryOptions($query, 'useDatFields', true);

            try {
                switch ($type) {
                    case 'select':
                        $query = $this->_selectDatFieldQueryTranslator($query, $datFieldsEnabled);
                        break;
                    case 'insert':
                        $query = $this->_insertDatFieldQueryTranslator($query, $datFieldsEnabled);
                        break;
                    case 'update':
                        $query = $this->_updateDatFieldQueryTranslator($query, $datFieldsEnabled);
                        break;
                    case 'delete':
                        $query = $this->_deleteDatFieldQueryTranslator($query, $datFieldsEnabled);
                        break;
                }
            } catch (\Error $err) {
                // debug($err);
                Log::Error(sprintf('Error while processing datfields in query: %s', $err->getMessage()));
            }

            // Apply original driver translator transformations
            $parentTranslator = parent::QueryTranslator($type);
            $query = $parentTranslator($query);

            return $query;
        };
    }

    /**
     * @inheritDoc
     */
    public function translateExpression($expression, Query $query, JsonTypeMap $jsonTypes)
    {
        if (is_array($expression)) {
            return array_map(function ($expr) use ($query, $jsonTypes) {
                return $this->translateExpression($expr, $query, $jsonTypes);
            }, $expression);
        }

        if (is_string($expression)) {
            return $this->_translateRawSQL($expression, $query, $jsonTypes);
        }

        if ($expression instanceof ComparisonExpression) {
            return $this->_translateComparisonExpression($expression, $query, $jsonTypes);
        }

        if ($expression instanceof OrderByExpression) {
            return $this->_translateOrderByExpression($expression, $query, $jsonTypes);
        }

        if ($expression instanceof IdentifierExpression) {
            return $this->_translateIdentifierExpression($expression, $query, $jsonTypes);
        }

        if ($expression instanceof UnaryExpression) {
            return $this->_translateUnaryExpression($expression, $query, $jsonTypes);
        }

        if ($expression instanceof BetweenExpression) {
            return $this->_translateBetweenExpression($expression, $query, $jsonTypes);
        }

        // This one is central as it solely allows nested expressions to be correctly replaced
        if ($expression instanceof QueryExpression) {
            $expression->iterateParts(
                function ($expr) use ($query, $jsonTypes) {
                    return $this->translateExpression($expr, $query, $jsonTypes);
                }
            );

            return $expression;
        }

        // For others ExpressionInterface instance, simply sneak in to update content
        if ($expression instanceof ExpressionInterface) {
            $expression->traverse(
                function ($expr) use ($query, $jsonTypes) {
                    return $this->translateExpression($expr, $query, $jsonTypes);
                }
            );
        }

        return $expression;
    }

    /**
     * Returns the model used by query if it is instance of Cake\ORM\Query
     * Otherwise returns null
     *
     * @param \Cake\Database\Query $query Query
     * @return string|null
     */
    protected function _getAliasFromQuery(Query $query): ?string
    {
        if ($query instanceof ORMQuery) {
            return $query->getRepository()->getAlias();
        }

        return null;
    }

    /**
     * Reads available options in query
     *
     * An option key can be provided to target one specific option. If option is not available, method will return the provided fallback
     *
     * @param \Cake\Database\Query $query Query
     * @param  string|null $option                 Option name
     * @param  mixed  $fallback               Fallback value
     * @return mixed
     */
    protected function _getQueryOptions(Query $query, ?string $option = null, $fallback = null)
    {
        $options = $query instanceof ORMQuery ? $options = $query->getOptions() : [];

        return $option ? $options[$option] ?? $fallback : $options;
    }

    /**
     * Processes casters queue and applies it to a whole row
     * For SELECT statements, the selectmap must be provided in order to
     * parse correctly selected fields from JSON field
     *
     * @param  array $row                   Row data
     * @param  array $casters               Casters queue
     * @param \Cake\Database\Query $query   Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap|null $selectmap JSON type map for selected fields. Do not use if not in SELECT statement
     * @return array
     */
    protected function _castRow(array $row, array $casters, Query $query, ?JsonTypeMap $selectmap = null): array
    {
        $decoder = function ($value) {
            return json_decode($value, true);
        };

        foreach ($casters as $datfield => $caster) {
            try {
                if ($selectmap) {
                    // Check select type map for automapped aliased field that's need to be decoded before sent to type callback
                    $type = $selectmap->type($datfield);
                    if ($type && $type !== '__auto_json__' && !$this->isDatField($datfield)) {
                        /** @var array $row */
                        $row = $this->applyCallbackToData($datfield, $row, $decoder);
                    }
                }

                /** @var array $row */
                $row = $this->applyCallbackToData($datfield, $row, $caster, $row, $query);
            } catch (MissingPathInDataDatFieldException $err) {
                // Simply ignore error as we don't want to raise an error in case of missing key
            }
        }

        return $row;
    }

    /**
     * Translates a given value to be usable for JSON comparisons by casting it to JSON if it's not a string
     *
     * @param  mixed $value                   Value to translate
     * @param \Cake\Database\Query $query     Query
     * @param  callable|null $caster          Caster to apply on value before casting it based on core type
     * @return mixed
     */
    protected function _castValue($value, Query $query, ?callable $caster = null)
    {
        // Apply JSON type caster to value if available
        if (is_callable($caster)) {
            $value = $caster($value);
        }

        // Null case
        if (is_null($value)) {
            $value = $query->newExpr("CAST('null' AS JSON)");
        }

        // Boolean case, simply update value to its stringified JSON counterpart
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
            $value = $query->newExpr("CAST({$value} AS JSON)");
        }

        // Number case, we must cast value to JSON to avoid unexpected results with numeric strings
        if (is_integer($value) || is_float($value)) {
            $value = $query->newExpr("CAST({$value} AS JSON)");
        }

        // String or array/object case
        if (is_string($value) || is_array($value)) {
            $value = json_encode($value);
            $value = $query->newExpr("CAST('{$value}' AS JSON)");
        }

        return $value;
    }

    /**
     * Update clauses for select queries
     *
     * @param \Cake\Database\Query $query Query
     * @param  bool $datFieldsEnabled  If `true` datfield notation is enabled and may be used
     * @return \Cake\Database\Query Updated query
     */
    protected function _selectDatFieldQueryTranslator(Query $query, bool $datFieldsEnabled): Query
    {
        // Update query type map
        $map = new JsonTypeMap($query->getTypeMap());
        $selectmap = new JsonTypeMap($query->getSelectTypeMap());

        // Translate select clause
        if ($datFieldsEnabled) {
            $query->traverse(function ($part, $clause) use ($query, $map, &$selectmap) {
                if ($clause === 'select' && !empty($part)) {
                    $query = $this->_translateSelect($query, $selectmap);
                }

                // Translate group clause
                if ($clause === 'group' && !empty($part)) {
                    $query = $query->group(array_map(function ($field) {
                        /** @phpstan-ignore-next-line */
                        return (string)$this->translateDatField($field);
                    }, $part), true);
                }

                // Translate associations
                if ($clause === 'join' && !empty($part)) {
                    foreach ($part as $name => $joint) {
                        $joint['conditions']->traverse(function ($e) use ($query, $map) {
                              $this->translateExpression($e, $query, $map);
                        });
                    }
                }

                if ($part instanceof ExpressionInterface) {
                    $this->translateExpression($part, $query, $map);
                }
            });
        }

        $query->setTypeMap($map->getRegularTypeMap());
        $query->setSelectTypeMap($selectmap->getRegularTypeMap());

        // Registers JSON types to be applied to incoming data
        $casters = $selectmap->getCasters($query, 'toPHP') + $map->getCasters($query, 'toPHP');
        if (!empty($casters)) {
            $query->decorateResults(function ($row) use ($casters, $query, $selectmap) {
                $row = $this->_castRow($row, $casters, $query, $selectmap);

                return $row;
            });
        }

        return $query;
    }

    /**
     * Update clauses for insert queries to apply JSON types
     *
     * @param \Cake\Database\Query $query Query
     * @param  bool $datFieldsEnabled  If `true` datfield notation is enabled and may be used
     * @return \Cake\Database\Query Updated query
     */
    protected function _insertDatFieldQueryTranslator(Query $query, bool $datFieldsEnabled): Query
    {
        // Update query type map
        $map = new JsonTypeMap($query->getTypeMap());
        $query->setTypeMap($map->getRegularTypeMap());
        $casters = $map->getCasters($query, 'toDatabase');

        // We need to apply JSON type map to outgoing data
        $expression = $query->clause('values');
        $rows = $expression->getValues();
        foreach ($rows as &$row) {
            $row = $this->_castRow($row, $casters, $query);
        }
        $expression->setValues($rows);

        return $query;
    }

    /**
     * Update clauses for update queries to apply JSON types and parse where condition
     *
     * @param \Cake\Database\Query $query Query
     * @param  bool $datFieldsEnabled  If `true` datfield notation is enabled and may be used
     * @return \Cake\Database\Query Updated query
     */
    protected function _updateDatFieldQueryTranslator(Query $query, bool $datFieldsEnabled): Query
    {
        // Update query type map
        $map = new JsonTypeMap($query->getTypeMap());
        $query->setTypeMap($map->getRegularTypeMap());

        // We need to apply JSON type map to outgoing data
        $set = $query->clause('set');
        $set->iterateParts(function ($expr) use ($query, $map) {
            if ($expr instanceof ComparisonExpression) {
                return $this->translateSetDatField($expr, $query, $map);
            }

            return $expr;
        });

        // No where parsing if datfields are disabled
        if ($datFieldsEnabled) {
            $where = $query->clause('where');
            $this->translateExpression($where, $query, $map);
        }

        return $query;
    }

    /**
     * Update clauses for delete queries to parse where conditions
     *
     * @param \Cake\Database\Query $query Query
     * @param  bool $datFieldsEnabled  If `true` datfield notation is enabled and may be used
     * @return \Cake\Database\Query Updated query
     */
    protected function _deleteDatFieldQueryTranslator(Query $query, bool $datFieldsEnabled): Query
    {
        if (!$datFieldsEnabled) {
            return $query;
        }

        $where = $query->clause('where');
        $map = new JsonTypeMap($query->getTypeMap());
        $query->setTypeMap($map->getRegularTypeMap());
        $this->translateExpression($where, $query, $map);

        return $query;
    }

    /**
     * Translates a between expression
     *
     * @param \Cake\Database\Expression\BetweenExpression $expression Expression
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return \Cake\Database\Expression\BetweenExpression Translated expression
     */
    protected function _translateBetweenExpression(
        BetweenExpression $expression,
        Query $query,
        JsonTypeMap $jsonTypes
    ): BetweenExpression {
        $field = $this->translateDatField($expression->getField());
        $expression->setField($field);

        if ($this->isDatField($field)) {
            $caster = $jsonTypes->getCaster($field, $query);
            $reflection = new \ReflectionClass($expression);
            $from = $reflection->getProperty('_from');
            $to = $reflection->getProperty('_to');
            $from->setAccessible(true);
            $to->setAccessible(true);
            $tfrom = $this->_castValue($from->getValue($expression), $query, $caster);
            $tto = $this->_castValue($to->getValue($expression), $query, $caster);
            $from->setValue($expression, $tfrom);
            $to->setValue($expression, $tto);
        }

        return $expression;
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
     * @param \Cake\Database\Expression\ComparisonExpression $expression ComparisonExpression expression
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return \Cake\Database\Expression\ComparisonExpression|\Cake\Database\Expression\QueryExpression Updated expression
     */
    protected function _translateComparisonExpression(
        ComparisonExpression $expression,
        Query $query,
        JsonTypeMap $jsonTypes
    ) {
        $field = $expression->getField();

        // Checks if it's a datfield and transform value if needed
        if ($this->isDatField($field)) {
            $caster = $jsonTypes->getCaster($field, $query);
            // Disable alias for update and delete queries
            $repository = in_array($query->type(), ['update', 'delete']) ? false : null;
            $field = $this->translateDatField($field, false, $repository);
            $value = $expression->getValue();
            $operator = strtolower($expression->getOperator());
            if ($operator === 'in' && is_array($value)) {
                foreach ($value as &$item) {
                    $item = $this->_castValue($item, $query);
                }
            } else {
                $value = $this->_castValue($value, $query, $caster);
            }

            $expression->setValue($value);
            $expression->setField($field);
        }

        return $expression;
    }

    /**
     * Translates an IdentifierExpression. Identifier is always unquoted as it can be used
     * in complex OrderClauseExpression. Resulting DatFieldExpression is converted to string.
     *
     * @param \Cake\Database\Expression\IdentifierExpression $expression Expression
     * @param \Cake\Database\Query $query If `true returns indentifier instead of updated expression
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return string|\Cake\Database\Expression\IdentifierExpression SQL fragment or query expression
     */
    protected function _translateIdentifierExpression(
        IdentifierExpression $expression,
        Query $query,
        JsonTypeMap $jsonTypes
    ) {
        $field = $expression->getIdentifier();

        if ($this->isDatField($field)) {
            /** @phpstan-ignore-next-line */
            $field = (string)$this->translateDatField($field, true);
            $expression->setIdentifier($field);
        }

        return $expression;
    }

    /**
     * Translates IS NULL statements into a suitable SQL for JSON data
     *
     * @param  string          $datfield                Datfield
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return \Cake\Database\Expression\QueryExpression IS NULL Expression
     */
    protected function _translateIsNull(
        string $datfield,
        Query $query,
        JsonTypeMap $jsonTypes
    ): QueryExpression {
        /** @phpstan-ignore-next-line */
        $datfield = (string)$this->translateDatField($datfield);
        $ignoreMissingPath = $this->_getQueryOptions($query, 'ignoreMissingPath', false);

        return $ignoreMissingPath ?
          $query->newExpr("{$datfield} = CAST('null' AS JSON)") :
          $query->newExpr()->or([
            "{$datfield} IS" => null,
            $query->newExpr("{$datfield} = CAST('null' AS JSON)"),
        ]);
    }

    /**
     * Translates IS NOT NULL statements into a suitable SQL for JSON data
     *
     * @param  string          $datfield                Datfield
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return \Cake\Database\Expression\QueryExpression IS NULL Expression
     */
    protected function _translateIsNotNull(string $datfield, Query $query, JsonTypeMap $jsonTypes): QueryExpression
    {
        /** @phpstan-ignore-next-line */
        $datfield = (string)$this->translateDatField($datfield);

        return $query->newExpr("{$datfield} <> CAST('null' AS JSON)");
    }

    /**
     * Updates fields in order clause by JSON_EXTRACT equivalent
     *
     * @param \Cake\Database\Expression\OrderByExpression $expression Order expression
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return \Cake\Database\Expression\OrderByExpression Updated Order expression
     */
    protected function _translateOrderByExpression(
        OrderByExpression $expression,
        Query $query,
        JsonTypeMap $jsonTypes
    ): OrderByExpression {
        $expression->iterateParts(function ($fieldOrOrder, &$key) use ($query, $jsonTypes) {
            if ($fieldOrOrder instanceof OrderClauseExpression) {
                return $this->translateExpression($fieldOrOrder, $query, $jsonTypes);
            }

            if ($this->isDatField($fieldOrOrder)) {
                /** @phpstan-ignore-next-line */
                return (string)$this->translateDatField($fieldOrOrder);
            }

            if ($this->isDatField($key)) {
                /** @phpstan-ignore-next-line */
                $key = (string)$this->translateDatField($key);
            }

            return $fieldOrOrder;
        });

        return $expression;
    }

    /**
     * Parsed DatField as string or in SQL fragments
     *
     * @param  string   $fragment   SQL fragment to parse
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return \Lqdt\OrmJson\Database\Expression\DatFieldExpression|string Updated fragment
     */
    protected function _translateRawSQL(string $fragment, Query $query, JsonTypeMap $jsonTypes)
    {
        if ($this->isDatField($fragment)) {
            /** @var \Lqdt\OrmJson\Database\Expression\DatFieldExpression $expr */
            $expr = $this->translateDatField($fragment);

            return $expr;
        }

        // Avoid translating already CAST TO JSON strings as it can mess up
        if (preg_match('/^CAST\(.*AS JSON\)$/', $fragment)) {
            return $fragment;
        }

        return preg_replace_callback(
            '/[\w\.\*\[\]]+(@|->)[\w\.\*\[\]]+/',
            function (array $matches) {
                /** @var \Lqdt\OrmJson\Database\Expression\DatFieldExpression $expr */
                $expr = $this->translateDatField($matches[0]);

                return (string)$expr;
            },
            $fragment
        );
    }

    /**
     * Apply datfield notation to select queries and handle select typemap for JSON fields
     * It will also update provided JSON type map to allow casting of aliases datfields
     *
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $map JSON Type map
     * @return \Cake\Database\Query Updated query
     */
    protected function _translateSelect(Query $query, JsonTypeMap &$map): Query
    {
        $fields = $query->clause('select');
        $updatedFields = [];

        // Handle alias for selected datfields
        foreach ($fields as $alias => $field) {
            $palias = null;

            // If field is an expression, translate it
            if ($field instanceof ExpressionInterface) {
                $updatedFields[$alias] = $this->translateExpression($field, $query, $map);
                continue;
            }

            if (is_int($field)) {
                $updatedFields[$alias] = $field;
                continue;
            }

            // Regular field case
            if (!$this->isDatField($field)) {
                $updatedFields[$alias] = $this->_translateRawSQL($field, $query, $map);
                continue;
            }

            if (is_integer($alias) || $this->isDatField($alias)) {
                // No alias given or autogenerated, generates a valid one or query will fail
                // Also clean CakePHP processed alias to keep JSON structure
                // This will only work if using \Cake\ORM\Query

                // If field is selected without alias, we must link type to previous cakephp alias
                $palias = is_string($alias) ? $alias : null;
                $alias = $this->aliasDatField($field);

                // Restore model alias
                if ($query instanceof ORMQuery) {
                    $alias = sprintf('%s__%s', $this->_getAliasFromQuery($query), $alias);
                }
            } else {
                // When aliasing datfields, select type map will only receive alias with copied type
                // We must get back type from main type map and clears select type map
                $type = $query->getTypeMap()->toArray()[$field] ?? null;
                if ($type) {
                    // Add back JSON type to parse alias correctly
                    $map
                      ->addJsonType($field, $type)
                      ->clearRegularTypeMap($alias);
                }
            }

            // Update alias target
            $map->setAlias($palias ?? $field, $alias);
            $updatedFields[$alias] = $this->translateDatField($field);
        }

        $query->select($updatedFields, true);

        return $query;
    }

    /**
     * Converts and unary expression
     *
     * It's quite hacky as UnaryExpression doesn't expose getters for `value` and `operator`
     *
     * It is used to parse 'IS NULL' or 'IS NOT NULL' statements
     *
     * @param \Cake\Database\Expression\UnaryExpression $expression Expression
     * @param \Cake\Database\Query $query Query
     * @param \Lqdt\OrmJson\Database\JsonTypeMap $jsonTypes JSON type map
     * @return \Cake\Database\Expression\UnaryExpression|\Cake\Database\Expression\QueryExpression Updated expression
     */
    protected function _translateUnaryExpression(UnaryExpression $expression, Query $query, JsonTypeMap $jsonTypes)
    {
        $reflection = new \ReflectionClass($expression);
        $operator = $reflection->getProperty('_operator');
        $operator->setAccessible(true);
        $op = $operator->getValue($expression);
        $value = $reflection->getProperty('_value');
        $value->setAccessible(true);
        $value = $value->getValue($expression);

        switch ($op) {
            case 'IS NULL':
                $datfield = $value->getIdentifier();
                if ($this->isDatField($datfield)) {
                    $expression = $this->_translateIsNull($datfield, $query, $jsonTypes);
                }
                break;
            case 'IS NOT NULL':
                $datfield = $value->getIdentifier();
                if ($this->isDatField($datfield)) {
                    $expression = $this->_translateIsNotNull($datfield, $query, $jsonTypes);
                }
                break;
            default:
                if ($value instanceof ExpressionInterface) {
                    $this->translateExpression($value, $query, $jsonTypes);
                }
                break;
        }

        return $expression;
    }
}
