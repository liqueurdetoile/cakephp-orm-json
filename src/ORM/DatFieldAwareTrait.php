<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM;

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Lqdt\OrmJson\Database\DatFieldDriverInterface;
use Lqdt\OrmJson\Database\Driver\DatFieldMysql;
use Lqdt\OrmJson\Database\Schema\DatFieldTableSchema;
use Lqdt\OrmJson\Database\Schema\DatFieldTableSchemaInterface;
use Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo;
use Lqdt\OrmJson\ORM\Association\DatFieldBelongsToMany;
use Lqdt\OrmJson\ORM\Association\DatFieldHasMany;
use Lqdt\OrmJson\ORM\Association\DatFieldHasOne;

/**
 * This trait will take care of handling driver and datfield associations.
 *
 * It is available natively within DatFieldBehavior
 *
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
trait DatFieldAwareTrait
{
    /**
     * If `true`, datfield support have been enabled
     *
     * @var bool
     */
    protected $_datFieldsEnabled = false;

    /**
     * @inheritDoc
     */
    public function datFieldBelongsTo(string $associated, array $options = []): DatFieldBelongsTo
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo $association */
        $association = $this->_associations->load(DatFieldBelongsTo::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function datFieldHasOne(string $associated, array $options = []): DatFieldHasOne
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldHasOne $association */
        $association = $this->_associations->load(DatFieldHasOne::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function datFieldHasMany(string $associated, array $options = []): DatFieldHasMany
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldHasMany $association */
        $association = $this->_associations->load(DatFieldHasMany::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function datFieldBelongsToMany(string $associated, array $options = []): DatFieldBelongsToMany
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldBelongsToMany $association */
        $association = $this->_associations->load(DatFieldBelongsToMany::class, $associated, $options);

        return $association;
    }

    /**
     * Finder that enables datfield support by upgrading query connection
     * It does not replace schema, so JSON types will not be available
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query Updated query
     */
    public function findDatfields(Query $query): Query
    {
        if ($this->_datFieldsEnabled === false) {
            $connection = $this->getUpgradedConnectionForDatFields($query->getConnection());
            $query->setConnection($connection);
        }

        return $query;
    }

    /**
     * Finder that enables datfield support by upgrading connection
     *
     * @param \Cake\ORM\Query $query [description]
     * @return \Cake\ORM\Query [description]
     */
    public function findJson(Query $query): Query
    {
        return $this->findDatfields($query);
    }

    /**
     * Returns the downgraded version of a connection ot the same connection if not already upgraded
     *
     * @param \Cake\Database\Connection $connection Connection
     * @return \Cake\Database\Connection Downgraded connection
     */
    public function getDowngradedConnectionForDatFields(Connection $connection): Connection
    {
        $driver = $connection->getDriver();
        $name = $connection->configName();

        if (!($driver instanceof DatFieldDriverInterface)) {
            return $connection;
        }

        // Find previous connection name and restores it on table
        $name = str_replace('_dfm', '', $name);
        $connection = ConnectionManager::get($name);

        /**
         * @var \Cake\Database\Connection $connection
        */
        return $connection;
    }

    /**
     * Returns the upgraded version of a connection or the same connection if already upgraded
     *
     * @param \Cake\Database\Connection $connection Connection
     * @return \Cake\Database\Connection Upgraded connection
     */
    public function getUpgradedConnectionForDatFields(Connection $connection): Connection
    {
        $driver = $connection->getDriver();
        $name = $connection->configName();

        if ($driver instanceof DatFieldDriverInterface) {
            return $connection;
        }

        // Upgrades connection with a new name
        $name .= '_dfm';

        // Returns upgraded connection if already created
        try {
            /**
             * @var \Cake\Database\Connection $connection
            */
            $connection = ConnectionManager::get($name);
        } catch (MissingDatasourceConfigException $err) {
            // We need to find out which database server is used
            $db = get_class($driver);

            switch ($db) {
                case Mysql::class:
                    $datfieldDriver = DatFieldMysql::class;
                    break;
                default:
                    throw new \Exception('DatField driver can not be used with your database system');
            }

            // Creates new connection with upgraded driver and upgraded schema
            // tableSchema option is only supported from CakePHP ^3.9
            // Connection class will be overriden by config one
            // Connection driver will always be overriden by upgraded one
            /** @var array<string, mixed> $config */
            $config = array_merge(
                [
                  'className' => Connection::class,
                  'tableSchema' => DatFieldTableSchema::class,
                ],
                $connection->config(),
                [
                  'driver' => $datfieldDriver,
                ]
            );

            ConnectionManager::setConfig($name, $config);

            /**
             * @var \Cake\Database\Connection $connection
            */
            $connection = ConnectionManager::get($name);
        }

        return $connection;
    }

    /**
     * Process a TableSchema instance to import it into a regular schema and returns it
     *
     * @param \Cake\Database\Schema\TableSchemaInterface $schema Schema to process
     * @param string|null $classname Class name to use for returned schema
     * @return \Cake\Database\Schema\TableSchemaInterface downgraded Schema
     */
    public function getDowngradedSchemaForDatFields(
        TableSchemaInterface $schema,
        ?string $classname = null
    ): TableSchemaInterface {
        if (!($schema instanceof DatFieldTableSchemaInterface)) {
            return $schema;
        }

        $columns = [];

        foreach ($schema->columns() as $name) {
            if (!$schema->isDatField($name)) {
                $columns[$name] = $schema->getColumn($name);
            }
        }

        $classname = $classname ?? TableSchema::class;

        return new $classname($schema->name(), $columns);
    }

    /**
     * Process a regular schema to import it into a DatFieldTableSchema instance and returns it
     *
     * @param \Cake\Database\Schema\TableSchemaInterface $schema Schema to process
     * @param string|null $classname Class name to use for returned schema
     * @return \Lqdt\OrmJson\Database\Schema\DatFieldTableSchemaInterface Upgraded Schema
     */
    public function getUpgradedSchemaForDatFields(
        TableSchemaInterface $schema,
        ?string $classname = null
    ): DatFieldTableSchemaInterface {
        if ($schema instanceof DatFieldTableSchemaInterface) {
            return $schema;
        }

        $columns = [];

        foreach ($schema->columns() as $name) {
            $columns[$name] = $schema->getColumn($name);
        }

        $classname = $classname ?? DatFieldTableSchema::class;

        return new $classname($schema->name(), $columns);
    }

    /**
     * Upgrades table driver to allow use of DatFields
     *
     * @param bool $enabled   If set to false, connection will be resumed to previous one
     * @return \Cake\ORM\Table
     */
    public function useDatFields(bool $enabled = true): Table
    {
        if (!$this instanceof Table) {
            throw new \RuntimeException('DatFieldAwareTrait can only be used on a Table instance');
        }

        return $enabled ?
          $this->_upgradeConnectionForDatFields($this) :
          $this->_downgradeConnectionForDatFields($this);
    }

    /**
     * Reverts connection upgrade and resume normal connection use
     *
     * @param \Cake\ORM\Table $table Table to be upgraded
     * @return \Cake\ORM\Table
     */
    protected function _downgradeConnectionForDatFields(Table $table): Table
    {
        if ($this->_datFieldsEnabled === false) {
            return $table;
        }

        $connection = $table->getConnection();
        $connection = $this->getDowngradedConnectionForDatFields($connection);
        $classname = $connection->config()['tableSchema'] ?? null;
        $schema = $this->getDowngradedSchemaForDatFields($table->getSchema(), $classname);
        $table->setConnection($connection);
        $table->setSchema($schema);
        $this->_datFieldsEnabled = false;

        return $table;
    }

    /**
     * Upgrades connection and schema for the table to use DatFieldMysqlDriver and JSON types
     *
     * @param \Cake\ORM\Table $table Table to be upgraded
     * @return \Cake\ORM\Table
     */
    protected function _upgradeConnectionForDatFields(Table $table): Table
    {
        if ($this->_datFieldsEnabled === true) {
            return $table;
        }

        $connection = $table->getConnection();
        $connection = $this->getUpgradedConnectionForDatFields($connection);
        $classname = $connection->config()['tableSchema'] ?? null;
        $schema = $this->getUpgradedSchemaForDatFields($table->getSchema(), $classname);
        $table->setConnection($connection);
        $table->setSchema($schema);
        $this->_datFieldsEnabled = true;

        return $table;
    }
}
