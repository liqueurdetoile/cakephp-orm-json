<?php
/**
 * JSON behavior for cakePHP framework
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
namespace Lqdt\OrmJson\Model\Behavior;

use \ArrayObject;
use Adbar\Dot;
use Cake\Core\Exception\Exception;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Lqdt\OrmJson\Database\Driver\DatFieldMysql;
use Lqdt\OrmJson\Utility\DatField;

/**
 * This CakePHP behavior adds support to performs mysql queries into JSON fields
 *
 * You can use any construct similar to classic query building based on field/value array
 * or full query string.
 *
 * JSON fields must be called in specific *datfield* format like this : <tt>path@[Model.]field</tt> and they will be turned
 * into JSON_EXTRACT short notation like <tt>[Model.]fieldname->"$.path"</tt>
 *
 * @version 2.0.0
 * @since   1.0.0
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
class DatFieldBehavior extends Behavior
{
    private $_extractOnSelect = false;
    private $_keepNestedOnExtract = false;
    private $_extractAliasSeparator = '_';
    private $_extractAliasLowercased = true;
    private $_extractAliasTemplate = '{{model}}{{separator}}{{field}}{{separator}}{{path}}';
    private $_foreignKeys = [];

    /**
     * At initialization the behavior will check current connection driver.
     *
     * If current connection driver is not DatFieldMysql, it will load/create a new collection with this driver
     *
     * @version 1.0.0
     * @since   2.0.0
     * @param   array     $config Table configuration
     * @return  void
     */
    public function initialize(array $config) : void
    {
        $connection = $this->getTable()->getConnection();
        $config = $connection->config();
        $driver = $connection->getDriver();

        if ($driver instanceof DatFieldMysql) {
            return;
        }

        if (!$driver instanceof Mysql) {
            throw new Exception('DatField driver can only be used with Mysql');
        }

        if (!$driver->supportsNativeJson()) {
            throw new Exception('Your mysql server does not support JSON columns. Please upgrade to version 5.7.0 or above');
        }

        // Try to get already created datfield connection
        $name = $config['name'] . '__DatFieldMysql__';
        try {
            $connection = ConnectionManager::get($name);
        } catch (\Exception $err) {
            $config['name'] = $name;
            $config['className'] = 'Cake\Database\Connection';
            $config['driver'] = 'Lqdt\OrmJson\Database\Driver\DatFieldMysql';
            ConnectionManager::setConfig($name, $config);
            $connection = ConnectionManager::get($name);
        }

        $this->getTable()->setConnection($connection);
    }

    /**
     * Parse datfield from raw data used in newEntity or PatchEntity methods into nested array values
     *
     * When using patchEntity, a call to jsonMerge must be done to merge old and new values or
     * new data will simply replaces previous one in the JSON field.
     *
     * @version 1.0.0
     * @since   1.1.0
     * @param   Event         $event   Event
     * @param   ArrayObject   $data    Data
     * @param   ArrayObject   $options Options
     * @return  void
     * @see \Lqdt\OrmJson\Model\Entity\JsonTrait::jsonMerge()
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) : void
    {
        $dot = new Dot();
        $map = $data->getArrayCopy();

        foreach ($map as $field => $value) {
            // Convert datfield and parse dotfield
            if (DatField::isDatField($field)) {
                $path = DatField::buildAliasFromTemplate($field, '{{field}}.{{path}}', '.');
                $dot->set($path, $value);
                $data->offsetUnset($field);
            }
        }

        $dot = $dot->all();
        foreach ($dot as $field => $value) {
            $data->offsetSet($field, $value);
        }
    }

    public function getExtractOnSelect() : bool
    {
        return $this->_extractOnSelect;
    }

    public function setExtractOnSelect(bool $extract)
    {
        $this->_extractOnSelect = $extract;
        return $this->getTable();
    }

    public function getKeepNestedOnExtract() : bool
    {
        return $this->_keepNestedOnExtract;
    }

    public function getExtractAliasSeparator() : string
    {
        return $this->_extractAliasSeparator;
    }

    public function setExtractAliasSeparator($separator)
    {
        if ($separator === false) {
            $this->_keepNestedOnExtract = true;
            $this->_extractAliasSeparator = '\.';
        } else {
            $this->_keepNestedOnExtract = false;
            $this->_extractAliasSeparator = $separator === '.' ? '\.' : $separator;
        }
        return $this->getTable();
    }

    public function isExtractAliasLowercased() : bool
    {
        return $this->_extractAliasLowercased;
    }

    public function setExtractAliasLowercased(bool $lowercased)
    {
        $this->_extractAliasLowercased = $lowercased;
        return $this->getTable();
    }

    public function getExtractAliasTemplate() : string
    {
        return $this->_extractAliasTemplate;
    }

    public function setExtractAliasTemplate(string $template)
    {
        $this->_extractAliasTemplate = $template;
        return $this->getTable();
    }

    public function registerForeignKey(string $field, string $path = null)
    {
        if (DatField::isDatField($field)) {
            ['field' => $field, 'path' => $path, 'model' => $model] = DatField::getDatFieldParts($field, $this->getTable()->getAlias());
        } else {
            $model = $this->getTable()->getAlias();
        }

        $property = $path . '@' . $model . '.' . $field;

        $fk = compact('field', 'path', 'property');
        if (!in_array($fk, $this->_foreignKeys)) {
            $this->_foreignKeys[] = $fk;
        }
    }

    public function getForeignKeys()
    {
        return $this->_foreignKeys;
    }
}
