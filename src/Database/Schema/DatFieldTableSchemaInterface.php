<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Schema;

use Cake\Database\Schema\TableSchemaInterface;
use Lqdt\OrmJson\Database\JsonTypeMap;

/**
 * When a table schema implements this interface, its `typeMap` method should return
 * an array containing regular field type and datfield type definition as well.
 *
 * Datfield type definition can be either a regular CakePHP data type string string or an array which provides one or more of the following keys :
 * - `type => <string>` : regular CakePHP registered data type
 * - `marshal => <callable>` <callable> : Callback to apply on marshal operations, overrides data type marshal if one is defined
 * - `toPHP => <callable>` : Callback to apply on toPHP operations, overrides data type toPHP if one is defined
 * - `toDatabase => <callable>` : Callback to apply on toDatabase operations, overrides data type toDatabase if one is defined
 *
 * The callable callback will be provided with following arguments :
 * 1. Current value in data
 * 2. Full row of data
 * 3. Current query
 *
 * Returned value will be used to replace value at datfield path
 */
interface DatFieldTableSchemaInterface extends TableSchemaInterface
{
    /**
     * Utility function to check if a field is datfield and if it's v1 or v2 notation
     *
     * @param   mixed $field Field name
     * @return  int   0 for non datfield strings, 1 for path@field notation and 2 for field->path notation
     */
    public function isDatField($field = null): int;

    /**
     * Permanently register JSON type(s) in schema
     *
     * @param array|string $types  Datfield to type or array of [<datfield> => <type definition>,...]
     * @param array|string|null   $type   Type definition
     * @return void
     */
    public function setJsonTypes($types, $type = null): void;

    /**
     * Register transient JSON type(s) in schema
     *
     * These types will be removed after next call to DatFieldTableSchema::typeMap()
     *
     * @param array|string $types  Datfield to type or array of [<datfield> => <type definition>,...]
     * @param array|null   $type   Type definition
     * @return void
     */
    public function setTransientJsonTypes($types, $type = null): void;

    /**
     * Returns the JSON type map based on schema type map
     *
     * Transient JSON types have to be removed afterwards
     *
     * @return \Lqdt\OrmJson\Database\JsonTypeMap
     */
    public function getJsonTypeMap(): JsonTypeMap;

    /**
     * Clears permanent JSON types
     *
     * @return self
     */
    public function clearJsonTypes(): self;

    /**
     * Clears transient JSON types
     *
     * @return self
     */
    public function clearTransientJsonTypes(): self;
}
