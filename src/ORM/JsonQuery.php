<?php
namespace Lqdt\OrmJson\ORM;

use Adbar\Dot;
use Cake\ORM\Query;
use Cake\Database\Expression\QueryExpression;
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
        return $field . '->"$.' . $path .'"';
    }

    /**
     * Apply jsonFieldName to every property name detected in a string
     *
     * The regexp is a bit tricky to avoid collision with mail parameter value
     * that will be enclosed by quotes
     *
     * @version 1.0.0
     * @since   1.0.0
     * @param   string             $conditions String to be reworked
     * @return  string                         String with parsed fields/properties
     */
    public function jsonFieldsNameInString(string $conditions) : string
    {
        $ret = preg_replace_callback(
            '|([^\w]*)([\w\.]+@[\w\.]+)|i',
            function ($matches) {
                if (!preg_match('|[\'"]|', $matches[1])) {
                    return str_replace($matches[2], $this->jsonFieldName($matches[2]), $matches[0]);
                }
                return $matches[0];
            },
            $conditions
        );

        return $ret;
    }

    /**
     * Parse a statement with JSON_EXTRACT short notation. It will convert
     * usual operators to fit a mysql JSON field search
     *
     * @version 1.0.0
     * @since   1.0.0
     * @param   string       $field  Field call
     * @param   mixed        $value  Value to match against the field
     * @return  mixed                Array or string given the value type
     */
    public function jsonStatement(string $field, $value)
    {
        // Check operand presence and add = if needed
        if (false === strpos($field, ' ')) {
            $field .= ' =';
        };

        // Parse datfield notation
        $field = $this->jsonFieldsNameInString($field);

        if (is_null($value)) {
            // Convert null search
            return $field . ' ' . "CAST('null' AS JSON)";
        } else {
            // LIKE comparison statements must be surrounded by ""
            if (preg_match('/ like/i', $field)) {
                $value = '"' . $value . '"';
            }
            switch (gettype($value)) {
              case 'string':
                // Use native prepared statement to prevent SQL injection
                return [$field => $value];
              case 'integer':
              case 'double':
                // PDO statement are failing on float values
                return $field . ' ' . $value;
              case 'boolean':
                // PDO statement are also failing on boolean
                return $field . ' ' . ($value ? 'true': 'false');
              case 'array':
                // Use native prepared statement to prevent SQL injection
                return $field . ' ' . "CAST('" . json_encode($value) . "' AS JSON)";
              default:
                throw new Exception('Unsupported type for value : ' . gettype($value));
              break;
            }
        }
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
     * @param   string|boolean   $separator       Separator sting for field aliases name (dot is not allowed). Set it to false to return Resultset as associative array
     * @param   bool             $lowercasedKey   Force key alias to be lowercased (useful when using models name in datfield that must be capitalized)
     * @return  JsonQuery                    Self for chaining
     */
    public function jsonSelect($fields, $separator = '_', bool $lowercasedKey = false) : self
    {
        $fields = (array) $fields;
        $types = $this->getSelectTypeMap()->getTypes();

        foreach ($fields as $alias => $field) {
            // regular field
            if (!$this->isDatField($field)) {
                $this->select([$alias => $field]);
                continue;
            }

            $parts = explode('@', $field);

            if ($separator === false) {
                $this->_isAssoc = true;
                $this->_isDotted = true;
                $separator = '\.';
            }

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
     * Build a QueryExpression object given the conditions input while
     * parsing dotted fields as JSON_EXTRACT short notation.
     * The conditions are evaluated with jsonStatement to fit
     * JSON search compatibility
     *
     * @version 1.0.0
     * @since   1.0.0
     * @param   array|string    $conditions Conditions for WHERE clause
     * @param   string          $operand    value for QueryExpression::setConjunction. It can be OR or AND
     * @param   boolean         $not        If true, the condition(s) will be negate by a NOT()
     * @return  QueryExpression             CakePHP QueryExpression object usable with Query::where
     */
    public function jsonExpression($conditions, $operand = 'AND', $not = false) : QueryExpression
    {
        $conditions = (array) $conditions;
        $exp = $this->newExpr()->setConjunction($operand);

        foreach ($conditions as $field => $value) {
            if (is_string($field) && strtoupper($field) !== 'OR' && strtoupper($field) !== 'NOT') {
                if ($not) {
                    $exp->not($this->jsonStatement($field, $value));
                    continue;
                }
                $exp->add($this->jsonStatement($field, $value));
                continue;
            }

            if (strtoupper($field) === 'OR') {
                $exp->add($this->jsonExpression($value, 'OR', $not));
                continue;
            }

            if (strtoupper($field) === 'NOT') {
                $exp->add($this->jsonExpression($value, $operand, true));
                continue;
            }

            if (is_integer($field)) {
                $value = $this->jsonFieldsNameInString($value);
                if ($not) {
                    $exp->not($value);
                    continue;
                }
                $exp->add($value);
                continue;
            }
        }

        return $exp;
    }

    /**
     * Where clause builder
     * @version 1.0.0
     * @since   1.0.0
     * @param   string|array    $conditions Conditions for WHERE clause
     * @return  JsonQuery                   Self for chaining
     */
    public function jsonWhere($conditions) : self
    {
        $conditions = (array) $conditions;
        $this->where($this->jsonExpression($conditions));
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
     * @version 1.1.0
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
                        if ($this->_isAssoc) {
                            $dot = new Dot();
                            $dot->set(str_replace('\.', '.', $fieldname), $value);
                            $dot = $dot->all();
                            $key = array_keys($dot)[0];
                            $value = $dot[$key];
                            $result->set($key, $value);
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
                    if ($this->_isAssoc) {
                        $dot = new Dot();
                        $dot->set(str_replace('\.', '.', $fieldname), $value);
                        $dot = $dot->all();
                        $key = array_keys($dot)[0];
                        $value = $dot[$key];
                        $data[$key] = $value;
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
