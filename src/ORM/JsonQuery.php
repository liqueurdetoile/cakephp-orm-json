<?php
namespace Lqdt\OrmJson\ORM;

use Adbar\Dot;
use Cake\ORM\Query;
use Cake\Database\ExpressionInterface;
use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Core\Exception\Exception;
use Cake\Database\Connection;
use Cake\Datasource\ResultSetDecorator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Table;
use Cake\Database\Schema\TableSchema;

/**
 * Extends the core Query class to provide support for parsing
 * valid queries containing JSON fields properties.
 *
 * @version 1.0.0
 * @since 1.0.0
 * @license MIT
 * @author Liqueur de Toile <contact@liqueurdetoile.com>
 */

class JsonQuery extends Query
{
    /**
     * Stores the use of dots in fields names when selecting
     * @var bool
     */
    protected $_isDotted = false;

    /**
     * Stores the use of sorting on JSON fields values
     * @var bool
     */
    protected $_isJsonSorted = false;

    /**
     * Stores the request to fetch back selected JSON fields as associative array
     * @var bool
     */
    protected $_isAssoc = false;

    /**
     * Constructor
     * @version 1.0.0
     * @since   1.0.0
     * @param   Connection  $connection  Connection to use
     * @param   Table       $table       Table to use     *
     * @param   Query       $parentQuery Initial Query object
     */
    public function __construct(Connection $connection, Table $table, Query $parentQuery = null)
    {
        parent::__construct($connection, $table);

        // Copy properties of previous query in current query
        if (!empty($parentQuery)) {
            $props = get_object_vars($parentQuery);
            foreach ($props as $key=>$value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Utility function to check if a field is datfield
     * @version 1.0.0
     * @since   1.3.0
     * @param   string    $field Field name
     * @return  boolean
     */
    public function isDatField($field)
    {
        return false !== strpos($field, '@');
    }

    /**
     * Convert a property name given under datfield format
     * into a valid JSON_EXTRACT short notation usable in cakePHP standard queries
     *
     * @version 1.0.0
     * @since   1.0.0
     * @param   String      $datfield     Input field name
     * @return  String      Returns Mysql valid formatted name to query JSON
     */
    public function jsonFieldName(string $datfield) : string
    {
        $parts = explode('@', $datfield);
        $path = array_shift($parts);
        $field = array_shift($parts);

        if (empty($field)) {
            return $path;
        }

        return $field . '->"$.' . $path .'"';
    }

    /**
     * Apply jsonFieldName to every property name detected in a string, mainly used
     * to parse SQL fragments
     *
     * The regexp is a bit tricky to avoid collision with mail parameter value
     * that will be enclosed by quotes
     *
     * @version 1.0.0
     * @since   1.0.0
     * @param   string             $expression SQL fragment to be reworked
     * @return  string             Parsed string that contains modified SQL fragment
     */
    public function jsonFieldsNameinString(string $expression) : string
    {
        return preg_replace_callback(
            '|([^\w]*)([\w\.]+@[\w\.]+)|i',
            function ($matches) {
                if (!preg_match('|[\'"]|', $matches[1])) {
                    return str_replace($matches[2], $this->jsonFieldName($matches[2]), $matches[0]);
                }
                return $matches[0];
            },
            $expression
        );
    }

    /**
     * Returns a new QueryExpression built upon the parsing of the expression to
     * update datfield names
     * @version 1.0.0
     * @since   1.5.0
     * @param   string          $expression Raw expression to transform
     * @return  QueryExpression             QueryExpression
     */
    public function rawSqlConverter(string $expression) : QueryExpression
    {
        return $this->newExpr($this->jsonFieldsNameinString($expression));
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
    public function comparisonConverter(Comparison $expression)
    {
        $field = $expression->getField();

        if ($this->isDatField($field)) {
            $field = $this->jsonFieldName($field);
            $operator = $expression->getOperator();
            $value = $expression->getValue();

            if (is_null($value)) {
                // No PDO way to handle null
                return $this->newExpr($field . ' = ' . "CAST('null' AS JSON)");
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

                    return $this->newExpr($field . " $cleanoperator " . $value);

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

                    return $this->newExpr($field . " $cleanoperator " . ($value ? 'true': 'false'));

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
                        return $this->newExpr("JSON_CONTAINS(CAST('" . json_encode($value) . "' AS JSON), " . $field . ")");
                      default:
                        throw new Exception('Unsupported operator ' . $operator . ' with OBJECT/ARRAY data type');
                    }

                    return $this->newExpr($field . " $cleanoperator " . "CAST('" . json_encode($value) . "' AS JSON)");

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
    public function unaryExpressionConverter(UnaryExpression $expression) : UnaryExpression
    {
        $value = null;
        try {
            $expression->traverse(function ($exp) use (&$value) {
                $value = $this->expressionConverter($exp);
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
    public function queryExpressionConverter(QueryExpression $expression) : QueryExpression
    {
        $expression->iterateParts(function ($condition, $key) {
            return $this->expressionConverter($condition);
        });

        return $expression;
    }

    /**
     * Returns the appropriate ExpressionInterface regarding the incoming one
     *
     * @version 1.0.0
     * @since   1.5.0
     * @param   string|ExpressionInterface      $expression Incoming expression
     * @return  ExpressionInterface             Updated expression
     */
    public function expressionConverter($expression) : ExpressionInterface
    {
        if (is_string($expression)) {
            return $this->rawSqlConverter($expression);
        }

        if ($expression instanceof QueryExpression) {
            return $this->queryExpressionConverter($expression);
        }

        if ($expression instanceof Comparison) {
            return $this->comparisonConverter($expression);
        }

        if ($expression instanceof UnaryExpression) {
            return $this->unaryExpressionConverter($expression);
        }

        throw new Exception('Unmanaged expression');
    }

    /**
     * Add conditions to the query that can be matched against datfield value
     *
     * jsonWhere accepts exactly the same parameters than the regular Query::where method
     *
     * The conditions are first parsed in a regular QueryExpression. The result is then
     * converted to replace Comparison expressions in a suitable way to query
     * JSON fields in database.
     *
     * The conditions that are matching regular fields are kept intact, thus allowing
     * to mix regular/datfields comparisons in a single call to jsonWhere.
     *
     * @version 1.1.0
     * @since   1.0.0
     * @param   string|array|callable       $conditions Conditions for WHERE clause
     * @return  JsonQuery                   Self for chaining
     */
    public function jsonWhere($conditions) : self
    {
        if (is_object($conditions) && is_callable($conditions)) {
            $expression = $conditions($this->newExpr(), $this);
        } else {
            $expression = $this->newExpr($conditions);
        }

        $jsonExpression = $this->expressionConverter($expression);
        $this->where($jsonExpression);

        return $this;
    }

    /**
     * Adds support to fetch selected properties within a JSON field
     *
     * Aliases fields are now supported as well as the use of a dot as separator or in alias for the
     * resulting field name
     *
     * @version 1.3.0
     * @since   1.0.0
     * @param   string|array     $fields          Field name in datfield notation
     * @param   string|boolean   $separator       Separator string for field aliases name (dot is not allowed). Set it to false to return Resultset as associative array
     * @param   bool             $lowercasedKey   Force key alias to be lowercased (useful when using models name in datfield that must be capitalized)
     * @return  JsonQuery                    Self for chaining
     */
    public function jsonSelect($fields, $separator = '_', bool $lowercasedKey = false) : self
    {
        $fields = (array) $fields;
        $types = $this->getSelectTypeMap()->getTypes();

        if ($separator === false) {
            $this->_isAssoc = true;
            $this->_isDotted = true;
            $separator = '\.';
            $lowercasedKey = false;
        }

        foreach ($fields as $alias => $field) {
            // regular field
            if (!$this->isDatField($field)) {
                $this->select([$alias => $field]);
                continue;
            }

            $parts = explode('@', $field);

            if ($separator === '.' || false !== strpos($alias, '.')) {
                $this->_isDotted = true;
                // escape dots to avoid failure when executing query
                $separator = '\.';
            }

            $key = str_replace(
              '.',
              $separator,
              is_int($alias) ? $parts[1] . '.' . $parts[0] : $alias
            );

            if ($lowercasedKey) {
                $key = strtolower($key);
            }
            $this->select([$key => $this->jsonFieldName($field)]);
            $types[$key] = 'json';
        }

        $this->getSelectTypeMap()->setTypes($types);
        return $this;
    }

    /**
     * Parse sort options to fit `[["fieldname" => "direction"]]` pattern
     *
     * @version 1.0.0
     * @since   1.3.0
     * @param   string|array    $conditions Sort conditions
     * @return  array           Parsed conditions
     */
    public function parseSortConditions($conditions) : array
    {
        if (is_string($conditions)) {
            $conditions = [
            $conditions => 'ASC'
          ];
        } else {
            foreach ($conditions as $key => $value) {
                if (is_int($key)) {
                    $conditions[$value] = 'ASC';
                    unset($conditions[$key]);
                }
            }
        }
        return $conditions;
    }

    /**
     * Intercepts calls to clause('select') to enable or disable autofields
     *
     * When sorting, we must select custom fields to sort and thus disable autofields
     * even if no selection have been made. This wrapper prevents this behavior
     *
     * @inheritdoc
     *
     * @version 1.0.0
     * @since   1.3.0
     * @param   string    $name Clause name
     * @return  array           Array of clause
     */
    public function clause($name)
    {
        if ($name === 'select') {
            $selectedFields = parent::clause($name);
            foreach ($selectedFields as $alias => $field) {
                // Remove "fake" json fields used for sorting
                if (false !== strpos($alias, '__orderingSelectedField__')) {
                    unset($selectedFields[$alias]);
                }
            }
            // Enable/disable autofields based on remaining selected fields presence
            $this->enableAutoFields(empty($selectedFields));
        }

        return parent::clause($name);
    }


    /**
     * Sets up the sorting based on JSON fields value
     *
     * @version 1.0.0
     * @since   1.3.0
     * @param   string|array    $conditions Sorting conditions
     * @return  JsonQuery       self for chaining
     */
    public function jsonOrder($conditions) : self
    {
        $conditions = $this->parseSortConditions($conditions);

        $fields = array_keys($conditions);
        $types = $this->getSelectTypeMap()->getTypes();

        foreach ($fields as $field) {
            // Regular field
            if (!$this->isDatField($field)) {
                $this->order([$field => $conditions[$field]]);
                continue;
            }

            $parts = explode('@', $field);
            $key = '__orderingSelectedField__' . str_replace('.', '_', $parts[1] . '.' . $parts[0]);
            $this->order([$key => $conditions[$field]]);
            $this->select([$key => $this->jsonFieldName($field)]);
            $types[$key] = 'json';
            $this->_isJsonSorted = true;
        }

        $this->getSelectTypeMap()->setTypes($types);
        return $this;
    }

    /**
     * Wrapper for the genuine `\Cake\ORM\Query::all` method to allow dot in field names
     * and sorting
     *
     * When extracting data, it unescapes dotted field names and clean
     * json fields used for sorting and not requested
     *
     * @version 1.1.2
     * @since   1.3.0
     * @return  ResultSetInterface    Result set to iterate on
     */
    public function all() : ResultSetInterface
    {
        if (!$this->_isDotted && !$this->_isJsonSorted) {
            return parent::all();
        }

        // restoring dot separator
        $resultSet = parent::all();
        $alias = $this->getRepository()->getAlias();

        // Entities case
        if ($this->_hydrate) {
            $entities = [];

            foreach ($resultSet as $result) {
                $properties = $result->toArray();

                foreach ($properties as $fieldname => $value) {
                    // Remove "fake" json fields used for sorting
                    if (false !== strpos($fieldname, '__orderingSelectedField__')) {
                        $result->unsetProperty($fieldname);
                    } elseif (false !== strpos($fieldname, '\.')) {
                        $result->unsetProperty($fieldname);

                        // Clean fieldname from table alias
                        $fieldname = str_replace($alias . '\.', '', $fieldname);

                        if ($this->_isAssoc) {
                            $dot = new Dot();
                            $dot->set(str_replace('\.', '.', $fieldname), $value);
                            $dot = $dot->all();
                            $key = array_keys($dot)[0];
                            $value = $dot[$key];
                            $merged = ($result->get($key) ?? []) + $value;
                            $result->set($key, $merged);
                        } else {
                            $result->set(str_replace('\.', '.', $fieldname), $value);
                        }
                    }
                }

                $result->clean();
                $entities[] = $result;
            }

            return new ResultSetDecorator($entities);
        }

        // Array case
        $results = [];

        foreach ($resultSet as $result) {
            $data = [];
            foreach ($result as $fieldname => $value) {
                // Remove "fake" json fields used for sorting
                if (false === strpos($fieldname, '__orderingSelectedField__')) {
                    if ($this->_isAssoc && false !== strpos($fieldname, '\.')) {
                        // Clean fieldname from table alias
                        $fieldname = str_replace($alias . '\.', '', $fieldname);
                        $dot = new Dot();
                        $dot->set(str_replace('\.', '.', $fieldname), $value);
                        $dot = $dot->all();
                        $key = array_keys($dot)[0];
                        $value = $dot[$key];
                        $merged = ($data[$key] ?? []) + $value;
                        $data[$key] = $merged;
                    } else {
                        $data[str_replace('\.', '.', $fieldname)] = $value;
                    }
                }
            }
            $results[] = $data;
        }

        return new ResultSetDecorator($results);
    }
}
