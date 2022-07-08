<?php
declare(strict_types=1);

/**
 * JSON behavior for cakePHP framework
 *
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
namespace Lqdt\OrmJson\Model\Behavior;

use Adbar\Dot;
use ArrayObject;
use Cake\Core\Exception\Exception;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use CakephpTestSuiteLight\Sniffer\MysqlTriggerBasedTableSniffer;
use Lqdt\OrmJson\Database\Driver\DatFieldMysql;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;
use Lqdt\OrmJson\ORM\ObjectEntity;

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
    use DatFieldAwareTrait;

    /**
     * Default options for manipulating json data
     *
     * @var array
     */
    protected $_defaultOptions = [
      'jsonReplace' => false,
      'keepJsonNested' => false,
      'jsonDateTimeTemplate' => 'Y-m-d M:m:s',
      'jsonPropertyTemplate' => '{{field}}{{separator}}{{path}}',
      'jsonSeparator' => '_',
      'parseJsonAsObject' => false,
    ];

    /**
     * Stores custom configuration for json fields
     *
     * @var array
     */
    protected $_jsonConfig = [];

    /**
     * @var array
     */
    private $_foreignKeys = [];

    /**
     * At initialization the behavior will check current connection driver nad upgrades it if needed.
     * It also sets up default entity class as ObjectEntity to includes behavior on fallback
     *
     * If current connection driver is not DatFieldMysql, it will upgrade it.
     *
     * @version 1.0.0
     * @since   2.0.0
     * @param   array $config Table configuration
     * @return  void
     */
    public function initialize(array $config): void
    {
        $this->getTable()->setEntityClass(ObjectEntity::class);
        $this->_upgradeConnection($this->getTable()->getConnection());
    }

    /**
     * Upgrades table connection to use DatFieldMysql driver
     *
     * @param \Cake\Database\Connection $connection Connection used by table
     * @return void
     */
    protected function _upgradeConnection(Connection $connection): void
    {
        $datFieldEnabled = strpos($connection->configName(), '_dfm') !== false;

        if ($datFieldEnabled) {
            return;
        }

        $name = $connection->configName() . '_dfm';

        try {
            /**
             * @var \Cake\Database\Connection $connection
            */
            $connection = ConnectionManager::get($name);
        } catch (\Cake\Datasource\Exception\MissingDatasourceConfigException $err) {
            // Checks that driver can be upgraded
            $driver = $connection->getDriver();

            // Edge case where driver have been statically configured in config
            if ($driver instanceof DatFieldMysql) {
                return;
            }

            // Checks that driver is Mysql based
            if (!$driver instanceof Mysql) {
                throw new Exception('DatField driver can only be used with Mysql');
            }

            // Ensure driver is connected before checking JSON support
            if (!$driver->isConnected()) {
                $driver->connect();
            }

            // Checks JSON support with backwards compatibility
            $jsonEnabled = method_exists($driver, 'supports') ?
              $driver->supports($driver::FEATURE_JSON) :
              $driver->supportsNativeJson();

            if (!$jsonEnabled) {
                throw new Exception(
                    'Your mysql server does not support JSON columns. Please upgrade to version 5.7.0 or above'
                );
            }

            if ($driver->isAutoQuotingEnabled()) {
                Log::warning(
                    'Cakephp identifiers autoquoting will be disabled as it prevents mysql operations on JSON fields.'
                );
            }

            // Creates new connection with upgraded driver
            $config = array_merge(
                ['className' => 'Cake\Database\Connection'],
                $connection->config(),
                [
                  'driver' => DatFieldMysql::class,
                  'quoteIdentifiers' => false,
                ]
            );

            /**
             * Adds table sniffer when in unit testing
             *
             * @see https://github.com/vierge-noire/cakephp-test-suite-light
             */
            if (getenv('TESTING') === '1') {
                $config['tableSniffer'] = MysqlTriggerBasedTableSniffer::class;
            }

            ConnectionManager::setConfig($name, $config);
            /**
             * @var \Cake\Database\Connection $connection
            */
            $connection = ConnectionManager::get($name);
        }

        $this->getTable()->setConnection($connection);
    }

    /**
     * Parse datfield keys from raw data used in newEntity or PatchEntity methods into nested array values
     *
     * When using patchEntity, a call to jsonMerge must be done to merge old and new values or
     * new data will simply replaces previous one in the JSON field.
     *
     * @version 1.0.0
     * @since   1.1.0
     * @param \Cake\Event\EventInterface $event Event
     * @param \ArrayObject $data Data
     * @param \ArrayObject $options Options
     * @return  void
     * @see     \Lqdt\OrmJson\Model\Entity\JsonTrait::jsonMerge()
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $dot = new Dot();
        $map = $data->getArrayCopy();
        $fields = [];
        $replacedKeys = $options['jsonReplace'] ?? [];

        foreach ($map as $field => $value) {
            // Convert datfield and parse dotfield
            if ($this->isDatField($field)) {
                $path = $this->renderFromDatFieldAndTemplate($field, '{{field}}.{{path}}', '.');
                $fieldname = $this->getDatFieldPart('field', $field, '{{field}}', '.');
                $replace = $this->getJsonFieldConfig($fieldname, (array)$options, 'jsonReplace');
                $dot->set($path, $value);
                $data->offsetUnset($field);
                if (!$replace) {
                    $fields[] = $fieldname;
                }
            }
        }

        $dot = $dot->all();
        foreach ($dot as $field => $value) {
            $data->offsetSet($field, $value);
        }

        $options->offsetSet('jsonFieldsToMerge', array_unique($fields));
    }

    /**
     * Handles merging of datfield data
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Cake\Datasource\EntityInterface $entity New entity
     * @param \ArrayObject $data Data
     * @param \ArrayObject $options Options
     * @return void
     */
    public function afterMarshal(
        EventInterface $event,
        EntityInterface $entity,
        \ArrayObject $data,
        \ArrayObject $options
    ): void {
        $keys = $options->offsetGet('jsonFieldsToMerge');
        $original = $entity->getOriginalValues();

        foreach ($original as $field => $previous) {
            if (!in_array($field, $keys)) {
                continue;
            }

            $current = $entity->get($field);

            // Skip if content are the same
            if ($previous === $current) {
                continue;
            }

            if (is_array($previous) && is_array($current)) {
                $previous = new Dot($previous);
                $previous->mergeRecursiveDistinct($current);
                $entity->set($field, $previous->all());
            }
        }
    }

    /**
     * Configures nehavior options for all or targetted json fields
     *
     * @param  array $config               Configuration to use
     * @return \Cake\ORM\Table
     */
    public function configureJsonFields(array $config): Table
    {
        $fields = $config['jsonFields'] ?? $this->getJsonFields();

        foreach ($fields as $field) {
            $this->_jsonConfig[$field] = $this->getJsonFieldConfig($field, $config);
        }

        return $this->getTable();
    }

    /**
     * Returns an array of JSON fields names stored in this model
     *
     * Filtering can be used to limit returned values
     *
     * @param array $filter = ['*'] Fields list for filtering results
     * @return array
     */
    public function getJsonFields(array $filter = ['*']): array
    {
        $schema = $this->getTable()->getSchema();
        $jsonFields = [];

        foreach ($schema->columns() as $column) {
            if ($schema->getColumnType($column) === 'json') {
                if ($filter === ['*'] || in_array($column, $filter)) {
                    $jsonFields[] = $column;
                }
            }
        }

        return $jsonFields;
    }

    /**
     * Returns configuration for a given field, optionnally for a given option
     *
     * @param  string $name                 Field name
     * @param  array  $runtimeConfig        Runtime config
     * @param  string|null $option          Option name
     * @return mixed Option value
     * @throws \Exception if option is provided and not available in field configuration
     */
    public function getJsonFieldConfig(string $name, array $runtimeConfig = [], ?string $option = null)
    {
        $config = $this->_jsonConfig[$name] ?? $this->_defaultOptions;
        $config = empty($runtimeConfig['jsonFields']) || in_array($name, $runtimeConfig['jsonFields']) ?
          array_merge($config, $runtimeConfig) :
          $config;

        if ($option && !array_key_exists($option, $config)) {
            throw new \Exception('[DatFieldBehavior] Unavailable option "' . $option . '" in json field configuration');
        }

        return $option ? $config[$option] : $config;
    }

    /**
     * Workaround for `getTable` deprecation as plugin should be kept compatible since 3.5+
     *
     * @return \Cake\ORM\Table
     */
    public function getTable(): Table
    {
        return method_exists($this, 'table') ? $this->table() : $this->getTable();
    }

    public function getExtractOnSelect(): bool
    {
        return $this->_extractOnSelect;
    }

    public function setExtractOnSelect(bool $extract): Table
    {
        $this->_extractOnSelect = $extract;

        return $this->getTable();
    }

    public function getKeepNestedOnExtract(): bool
    {
        return $this->_keepNestedOnExtract;
    }

    public function getExtractAliasSeparator(): string
    {
        return $this->_extractAliasSeparator;
    }

    /**
     * Set seprator when extracting json data into a field
     *
     * Passing false will reset it to default
     *
     * @param  string|bool $separator Separator
     * @return \Cake\ORM\Table
     */
    public function setExtractAliasSeparator($separator): Table
    {
        if (is_bool($separator)) {
            $this->_keepNestedOnExtract = true;
            $this->_extractAliasSeparator = '\.';
        } else {
            $this->_keepNestedOnExtract = false;
            $this->_extractAliasSeparator = $separator === '.' ? '\.' : $separator;
        }

        return $this->getTable();
    }

    public function isExtractAliasLowercased(): bool
    {
        return $this->_extractAliasLowercased;
    }

    public function setExtractAliasLowercased(bool $lowercased): Table
    {
        $this->_extractAliasLowercased = $lowercased;

        return $this->getTable();
    }

    public function getExtractAliasTemplate(): string
    {
        return $this->_extractAliasTemplate;
    }

    public function setExtractAliasTemplate(string $template): Table
    {
        $this->_extractAliasTemplate = $template;

        return $this->getTable();
    }

    public function hasDatMany(string $alias, array $options): Table
    {
        $fk = $options['foreignKey'] ?? null;

        if (empty($fk)) {
            throw new Exception('Foreign key must be set with hasDatMany');
        }

        $this->getTable()->hasMany($alias, $options);
        if (DatField::isDatField($fk)) {
            $this->getTable()->$alias->registerForeignKey($fk);
        }

        return $this->getTable();
    }

    public function registerForeignKey(string $field, ?string $path = null): Table
    {
        if (DatField::isDatField($field)) {
            ['field' => $field, 'path' => $path, 'model' => $model] = DatField::getDatFieldParts($field, $this->getTable()->getAlias());
        } else {
            $model = $this->getTable()->getAlias();
        }

        $property = $path . '@' . $field;

        $fk = compact('field', 'path', 'property');
        if (!in_array($fk, $this->_foreignKeys)) {
            $this->_foreignKeys[] = $fk;
        }

        return $this->getTable();
    }

    public function getForeignKeys(): array
    {
        return $this->_foreignKeys;
    }
}
