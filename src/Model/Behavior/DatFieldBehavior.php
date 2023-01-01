<?php
declare(strict_types=1);

/**
 * JSON behavior for cakePHP framework
 *
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
namespace Lqdt\OrmJson\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Lqdt\OrmJson\DatField\DatFieldParserTrait;
use Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo;
use Lqdt\OrmJson\ORM\Association\DatFieldBelongsToMany;
use Lqdt\OrmJson\ORM\Association\DatFieldHasMany;
use Lqdt\OrmJson\ORM\Association\DatFieldHasOne;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

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
    use DatFieldParserTrait;

    /**
     * At initialization the behavior will check current connection driver and upgrades it if needed.
     *
     * @param   array $config Table configuration
     * @return  void
     */
    public function initialize(array $config): void
    {
        $upgrade = $config['upgrade'] ?? false;

        if ($upgrade) {
            $this->useDatFields();
        }
    }

    /**
     * @inheritDoc
     */
    public function useDatFields(bool $enabled = true): Table
    {
        return $enabled ?
          $this->_upgradeConnectionForDatFields($this->_getTable()) :
          $this->_downgradeConnectionForDatFields($this->_getTable());
    }

    /**
     * Parse datfield keys from raw data used in newEntity or PatchEntity methods into nested array values
     * It alos take cares of applying Json type Map to inbound values
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \ArrayObject $data Data
     * @param \ArrayObject $options Options
     * @return  void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (!$this->_datFieldsEnabled) {
            return;
        }

        $copy = $data->getArrayCopy();

        // Enable merging for all fields if no option is given
        $merging = $options['jsonMerge'] ?? false;
        $forMerging = [];

        if ($merging === true) {
            $merging = ['*'];
        }

        // Loads JSON types
        $this->_setTransientJsonTypes($options['jsonTypeMap'] ?? null);
        /** @var \Lqdt\OrmJson\Database\Schema\DatFieldTableSchemaInterface $schema */
        $schema = $this->_getTable()->getSchema();
        $jmap = $schema->getJsonTypeMap();

        foreach ($copy as $field => $value) {
            // Convert datfield and parse dotfield
            if ($this->isDatField($field)) {
                $caster = $jmap->getCaster($field, null, 'marshal');
                if (!empty($caster)) {
                    $value = $caster($value);
                }
                $fieldname = $this->getDatFieldPart('field', $field);
                $this->setDatFieldValueInData($field, $value, $data);
                $data->offsetUnset($field);
                // Register field for later merging if enabled
                if ($merging && in_array('*', (array)$merging) || in_array($fieldname, (array)$merging)) {
                    $forMerging[] = $fieldname;
                }
            }
        }

        $options->offsetSet('jsonMerge', array_unique($forMerging));
    }

    /**
     * Handles merging of datfield data given each field configuration
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
        ArrayObject $data,
        ArrayObject $options
    ): void {
        if (!$this->_datFieldsEnabled) {
            return;
        }

        $this->jsonMerge($entity, $options->offsetGet('jsonMerge'));
    }

    /**
     * Enables transient JSON types provided through query options
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Cake\ORM\Query $query Query
     * @param \ArrayObject $options Options
     * @return \Cake\ORM\Query
     */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options): Query
    {
        if (!empty($options['jsonTypeMap'])) {
            $this->_setTransientJsonTypes($options['jsonTypeMap'] ?? null);
            // Force query to reload types
            $query->addDefaultTypes($this->_getTable());
        }

        return $query;
    }

    /**
     * Enables transient JSON types provided through query options
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->_setTransientJsonTypes($options['jsonTypeMap'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function datFieldBelongsTo(string $associated, array $options = []): DatFieldBelongsTo
    {
        $table = $this->_getTable();
        $options += ['sourceTable' => $table];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo $association */
        $association = $table->associations()->load(DatFieldBelongsTo::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function datFieldHasOne(string $associated, array $options = []): DatFieldHasOne
    {
        $table = $this->_getTable();
        $options += ['sourceTable' => $table];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldHasOne $association */
        $association = $table->associations()->load(DatFieldHasOne::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function datFieldHasMany(string $associated, array $options = []): DatFieldHasMany
    {
        $table = $this->_getTable();
        $options += ['sourceTable' => $table];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldHasMany $association */
        $association = $table->associations()->load(DatFieldHasMany::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function datFieldBelongsToMany(string $associated, array $options = []): DatFieldBelongsToMany
    {
        $table = $this->_getTable();
        $options += ['sourceTable' => $table];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldBelongsToMany $association */
        $association = $table->associations()->load(DatFieldBelongsToMany::class, $associated, $options);

        return $association;
    }

    /**
     * Workaround for `getTable` deprecation as plugin should be kept compatible since 3.5+
     *
     * @return \Cake\ORM\Table
     */
    protected function _getTable(): Table
    {
        return method_exists($this, 'table') ? $this->table() : $this->getTable();
    }

    /**
     * Registers the JSON types in table schema
     *
     * @param ?array $types  JSON types
     * @return void
     */
    protected function _setTransientJsonTypes(?array $types): void
    {
        if ($this->_datFieldsEnabled && !empty($types)) {
            /** @var \Lqdt\OrmJson\Database\Schema\DatFieldTableSchemaInterface $schema */
            $schema = $this->_getTable()->getSchema();

            $schema->setTransientJsonTypes($types);
        }
    }
}
