<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database;

use Cake\Database\Query;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Lqdt\OrmJson\Database\Expression\DatFieldExpression;
use Lqdt\OrmJson\DatField\DatFieldParserTrait;

/**
 * This extended type map handles cleaning of regular typemap and
 * provides the needed casters to transform values based on
 * JSON types definition
 *
 * You should not directly instantiate this class. It is used by query translator
 */
class JsonTypeMap
{
    use DatFieldParserTrait;

    /**
     * Stores Json types map
     *
     * @var array<string,string|array>
     */
    protected $_jsonTypeMap = [];

    /**
     * Stores cleaned regular type map
     *
     * @var \Cake\Database\TypeMap
     */
    protected $_regularTypeMap;

    /**
     * Constructor. Passing a typemap as argument will import it
     *
     * @param \Cake\Database\TypeMap $map TypeMap
     */
    public function __construct(?TypeMap $map = null)
    {
        if (!empty($map)) {
            $this->importTypeMap($map);
        }
    }

    /**
     * Returns the current regular typemap or null if no map have been imported
     *
     * @return \Cake\Database\TypeMap|null
     */
    public function getRegularTypeMap(): ?TypeMap
    {
        return $this->_regularTypeMap;
    }

    /**
     * Adds a JSON type definition
     *
     * If the key already exists, it will be overriden
     *
     * @param  string $datfield               Datfield
     * @param  string|array $type                   Date type or JSON type definition
     * @return self
     */
    public function addJsonType(string $datfield, $type): self
    {
        $this->_jsonTypeMap[$datfield] = $type;

        return $this;
    }

    /**
     * Adds JSON types definitions
     *
     * If a key already exists, it will be overriden
     *
     * @param  array $types   JSON types
     * @return self
     */
    public function addJsonTypes(array $types): self
    {
        foreach ($types as $datfield => $type) {
            $this->addJsonType($datfield, $type);
        }

        return $this;
    }

    /**
     * Utility method to clear out a non datfield alias that is kept in regular ty
     *
     * @param  string $alias               Alias
     * @return self
     */
    public function clearRegularTypeMap(string $alias): self
    {
        $types = $this->_regularTypeMap->getDefaults();
        unset($types[$alias]);
        $this->_regularTypeMap->setDefaults($types);

        return $this;
    }

    /**
     * Imports a regular typemap and splits types between regular and json ones
     *
     * @param \Cake\Database\TypeMap $map Map
     * @return self
     */
    public function importTypeMap(TypeMap $map): self
    {
        $extractor = function (array $types): array {
            $ret = [
              'regular' => [],
              'json' => [],
            ];

            foreach ($types as $field => $type) {
                $key = $this->isDatField($field) ? 'json' : 'regular';
                $field = $this->_getDatFieldKey($field);
                $ret[$key][$field] = $type;
            }

            return $ret;
        };

        // Reset casters queue
        $this->_jsonTypeMap = [];
        $this->_regularTypeMap = new TypeMap();

        $defaults = $extractor($map->getDefaults());
        $this->_regularTypeMap->setDefaults($defaults['regular']);
        $this->_jsonTypeMap = $defaults['json'];

        $types = $extractor($map->getTypes());
        $this->_regularTypeMap->setTypes($types['regular']);
        $this->_jsonTypeMap = $types['json'] + $this->_jsonTypeMap;

        return $this;
    }

    /**
     * Registers an alias for a given datfield or as json data type to allow easy fallback
     * on JSON core data types
     *
     * @param string|\Lqdt\OrmJson\Database\Expression\DatFieldExpression $datfield Datfield
     * @param string $alias     Alias
     * @return void
     */
    public function setAlias($datfield, string $alias): void
    {
        // Try to find out types in current registered datfields
        $type = $this->type($datfield) ?? 'json';

        $this->_jsonTypeMap[$alias] = $type;
    }

    /**
     * Returns the caster matching the provided datfield if one is available
     *
     * @param string|array|\Cake\Database\ExpressionInterface $datfield Datfield
     * @param \Cake\Database\Query|null $query Query
     * @param  string   $operation               Casting operation
     * @return callable|null    Caster
     */
    public function getCaster($datfield, ?Query $query, string $operation = 'toDatabase'): ?callable
    {
        $type = $this->type($datfield);

        return $type !== null ? $this->_getCasterByType($type, $query, $operation) : null;
    }

    /**
     * Build the casters queue from the stored JSON types
     *
     * @param \Cake\Database\Query|null $query Query
     * @param string  $operation  Operation to perform
     * @return array<string, callable>
     */
    public function getCasters(?Query $query, string $operation = 'toDatabase'): array
    {
        $casters = [];

        foreach ($this->_jsonTypeMap as $key => $type) {
            $casters[$key] = $this->_getCasterByType($type, $query, $operation);
        }

        return $casters;
    }

    /**
     * Returns the stored JSON type definition for a datfield or null if none available
     *
     * @param string|array|\Cake\Database\ExpressionInterface $datfield Datfield
     * @return string|array|null  Type definition
     */
    public function type($datfield)
    {
        if ($datfield instanceof DatFieldExpression) {
            $key = $datfield->getDatFieldKey();
        } elseif (is_string($datfield)) {
            $key = $this->_getDatFieldKey($datfield);
        }

        if (empty($key)) {
            return null;
        }

        return $this->_jsonTypeMap[$key] ?? null;
    }

    /**
     * Get the caster for a given data type or JSON type definition
     *
     * @param  array|string $type JSON type definition or regular data type
     * @param \Cake\Database\Query|null $query Query
     * @param string  $operation  When using type, tells caster to use a given method
     * @return callable
     */
    protected function _getCasterByType($type, ?Query $query, string $operation = 'toDatabase'): callable
    {
        if (is_array($type)) {
            if (array_key_exists($operation, $type) && is_callable($type[$operation])) {
                $type = $type[$operation];
            } elseif (array_key_exists('type', $type) && is_string($type['type'])) {
                $type = $type['type'];
            }
        }

        if (is_callable($type) && !is_string($type)) {
            return $type;
        }

        if (is_string($type) && TypeFactory::getMap($type)) {
            $type = TypeFactory::build($type);
            $driver = $query ? $query->getConnection()->getDriver() : null;

            return function ($value) use ($driver, $type, $operation) {
                return $type->{$operation}($value, $driver);
            };
        }

        throw new \Exception('Unable to parse provided JSON type into a valid caster');
    }
}
