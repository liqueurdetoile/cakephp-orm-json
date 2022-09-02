<?php
declare(strict_types=1);

/**
 * Datfield trait overrides regular EntityTrait in order to allow the
 * use of datfield syntax to access and manage properties in JSON fields
 *
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
namespace Lqdt\OrmJson\Model\Entity;

use Cake\Datasource\EntityTrait;
use Lqdt\OrmJson\DatField\DatFieldParserTrait;

/**
 * This trait adds useful methods to get and set values in JSON fields
 *
 * It superseded regular magic function to include json functions
 *
 * All methods can safely be called on regular fields
 *
 * @version 1.1.0
 * @since   1.0.0
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
trait DatFieldTrait
{
    use DatFieldParserTrait {
        DatFieldParserTrait::jsonMerge as protected _jsonMerge;
    }

    use EntityTrait {
        EntityTrait::get as protected _get;
        EntityTrait::isAccessible as protected _isAccessible;
        EntityTrait::isDirty as protected _isDirty;
        EntityTrait::set as protected _set;
        EntityTrait::setAccess as protected _setAccess;
        EntityTrait::setDirty as protected _setDirty;
    }

    /**
     * Removes one or many fields or datfields
     *
     * For regular fields, it's an alias for unset
     * For datfields, there's a huge difference as :
     * - Data is removed from JSON field
     * - Original data is updated to make operation reversible
     * - JSON field is flag as dirty to allow triggering persistence operations after deletion
     *
     * @param   string|array<string>     $field Field(s) or datfield(s) name to unset
     * @return  self
     * @see DatFieldTrait::unset
     */
    public function delete($field): self
    {
        $fields = (array)$field;

        foreach ($fields as $name) {
            if ($this->isDatField($name)) {
                $field = $this->getDatFieldPart('field', $name);

                if (!array_key_exists($field, $this->_fields)) {
                    continue;
                }

                if (!$this->hasDatFieldPathInData($name, $this)) {
                    continue;
                }

                if (!array_key_exists($field, $this->_original)) {
                    $this->_original[$field] = $this->_fields[$field];
                }

                $this->deleteDatFieldValueInData($name, $this);
                $this->setDirty($name, false);
                $this->setDirty($field, true);
            } else {
                $this->unset($name);
            }
        }

        return $this;
    }

    /**
     * Gets a value
     *
     * @param   string  $field    Field or Datfield name
     * @return  mixed             Field value (by reference)
     */
    public function &get(string $field)
    {
        if ($this->isDatField($field)) {
            $value = &$this->getDatFieldValueInData($field, $this, false);
        } else {
            $value = &$this->_get($field);
        }

        return $value;
    }

    /**
     * Checks if a field or a datfield is accessible
     *
     * If no accesibility status is stored at datfield level, field accessibility status will be used
     *
     * @param string $field Field or Datfield name
     * @return bool
     */
    public function isAccessible(string $field): bool
    {
        if ($this->isDatField($field)) {
            $key = $this->_getDatFieldKey($field);
            $field = $this->getDatFieldPart('field', $field);
            $accessible = $this->_accessible[$key] ?? null;

            return ($accessible === null && $this->_isAccessible($field)) || $accessible;
        }

        return $this->_isAccessible($field);
    }

    /**
     * Checks if the entity is dirty or if a single field or datfield of it is dirty.
     *
     * @param string|null $field The field to check the status for. Null for the whole entity.
     * @return bool Whether the field was changed or not
     */
    public function isDirty(?string $field = null): bool
    {
        return $this->_isDirty($this->_getDatFieldKey($field));
    }

    /**
     * Merge missing original values in one or more json fields
     *
     * @param  string|array<string>   $keys Fields to merge
     * @return self
     */
    public function jsonMerge($keys): self
    {
        $this->_jsonMerge($this, $keys);

        return $this;
    }

    /**
     * Set a value inside field JSON data or map to regular field
     *
     * Accessibility is checked at field and datfield level
     *
     * @param   string    $field Dafield or array of [datfield => value]
     * @param   mixed           $value      Value to set
     * @param   array           $options    Options
     * @return  self
     * @see EntityTrait::isAccessible
     */
    public function set($field, $value = null, array $options = []): self
    {
        // We need to rebuild options as in regular set
        if (!is_array($field) && !empty($field)) {
            $guard = false;
            $field = [$field => $value];
        } else {
            $guard = true;
            $options = (array)$value;
        }

        if (!is_array($field)) {
            throw new \InvalidArgumentException('Cannot set an empty field');
        }

        $options += ['setter' => true, 'guard' => $guard];

        foreach ($field as $name => $value) {
            if (!$this->isDatField($name)) {
                $this->_set($name, $value, $options);
                continue;
            }

            $key = $this->_getDatFieldKey($name);
            if ($options['guard'] === true && !$this->isAccessible($key)) {
                continue;
            }

            $name = $this->getDatFieldPart('field', $name);
            // Stores original field
            if (
                !array_key_exists($name, $this->_original) &&
                array_key_exists($name, $this->_fields) &&
                $this->get($key) !== $value
            ) {
                $this->_original[$name] = $this->_fields[$name];
            }

            $this->setDatFieldValueInData($key, $value, $this);
            $this->setDirty($key, true);
        }

        return $this;
    }

    /**
     * Stores whether a field or datfield value can be changed or set in this entity
     *
     * @param array<string>|string $field Single or list of fields to change its accessibility
     * @param bool $set True marks the field as accessible, false will
     * mark it as protected.
     * @return $this
     */
    public function setAccess($field, bool $set)
    {
        if ($field === '*') {
            return $this->_setAccess($field, $set);
        }

        foreach ((array)$field as $prop) {
            if ($this->isDatField($prop)) {
                $prop = $this->_getDatFieldKey($prop);
            }

            $this->_setAccess($prop, $set);
        }

        return $this;
    }

    /**
     * Sets the dirty status of a single field or datfield
     *
     * When procession a datfield, a check is done to ensure properties dirty state and field dirtiness are coherent
     *
     * @param string $field the field to set or check status for
     * @param bool $isDirty true means the field was changed, false means
     * it was not changed. Defaults to true.
     * @return $this
     */
    public function setDirty(string $field, bool $isDirty = true)
    {
        if (!$this->isDatField($field)) {
            if ($isDirty === false) {
                // clears all properties dirty state from field if any
                foreach ($this->_dirty as $f => $value) {
                    if (strpos($f, $field . '->') === 0) {
                        unset($this->_dirty[$f]);
                    }
                }
            }

            return $this->_setDirty($field, $isDirty);
        }

        $key = $this->_getDatFieldKey($field);
        $field = $this->getDatFieldPart('field', $field);

        if ($isDirty === false) {
            unset($this->_dirty[$key]);
            foreach ($this->_dirty as $f => $value) {
                if ($value === true && strpos($f, $field . '->') !== false) {
                    return $this;
                }
            }

            return $this->_setDirty($field, false);
        }

        $this->_dirty[$key] = true;
        $this->_dirty[$field] = true;
        unset($this->_errors[$field], $this->_invalid[$field]);

        return $this;
    }

    /**
     * Removes one or many fields or datfields
     *
     * Similarly to regular fields, unsetting a datfield will clears out dirty state and original data for the whole JSON field
     *
     * @param   string|array<string>     $field Field(s) or Datfield(s) name to unset
     * @return  self
     */
    public function unset($field): self
    {
        $fields = (array)$field;

        foreach ($fields as $name) {
            if ($this->isDatField($field)) {
                $field = $this->getDatFieldPart('field', $name);

                if (!array_key_exists($field, $this->_fields)) {
                    continue;
                }

                if (!$this->hasDatFieldPathInData($name, $this)) {
                    continue;
                }

                $this->deleteDatFieldValueInData($name, $this);
                $this->setDirty($field, false);
                unset($this->_original[$field]);
            } else {
                // We don't use parent method to avoid compatibility issues
                unset($this->_fields[$field], $this->_original[$field], $this->_dirty[$field]);
            }
        }

        return $this;
    }
}
