<?php
namespace Lqdt\Coj\ORM;

use Cake\ORM\Query;
use Cake\Database\Expression\QueryExpression;
use Cake\Core\Exception\Exception;
use Cake\Database\Connection;
use Cake\ORM\Table;

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
    public function __construct(Connection $connection, Table $table, Query $parentQuery = null)
    {
        parent::__construct($connection, $table);
        // Copy properties of parent in current query
        if (!empty($parentQuery)) {
            $props = get_object_vars($parentQuery);
            foreach ($props as $key=>$value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Convert a property name given under datfield format
     * into a valid JSON_EXTRACT short notation usable in cakePHP standard queries
     *
     * @method  jsonFieldName
     * @version 1.0.0
     * @since   1.0.0
     * @param   String      $field Input field name
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
     * @method  jsonFieldsNameInString
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
     * @method  jsonStatement
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
     * The returned property name will be aliased by replacing `@` and `.` with
     * `_` bye default to avoid errors. Custom separator string can be provided
     *
     * @method  jsonselect
     * @version 1.0.0
     * @since   1.0.0
     * @param   string|array     $fields     Field name in datfield notation
     * @param   string           $separator  Separator sting for field aliases name (dot is not allowed)
     * @return  jsonQuery                    Self for chaining
     */
    public function jsonselect($fields, string $separator = '_') : jsonQuery
    {
        $jsonfields = [];
        $fields = (array) $fields;
        foreach ($fields as $field) {
            $parts = explode('@', $field);
            $key = str_replace('.', $separator, $parts[1] . '.' . $parts[0]);
            $jsonfields[$key] = $this->jsonFieldName($field);
        }

        $this->select($jsonfields);
        return $this;
    }

    /**
     * Build a QueryExpression object given the conditions input while
     * parsing dotted fields as JSON_EXTRACT short notation.
     * The conditions are evaluated with jsonStatement to fit
     * JSON search compatibility
     *
     * @method  jsonExpression
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

    public function jsonwhere($conditions)
    {
        $this->where($this->jsonExpression($conditions));
        return $this;
    }
}
