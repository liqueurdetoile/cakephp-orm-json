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

use Adbar\Dot;
use Cake\Datasource\EntityTrait;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

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
    use DatFieldAwareTrait, EntityTrait {
        EntityTrait::get as protected _get;
        EntityTrait::has as protected _has;
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
     *
     * For JSON fields, il will updates original property and dirty state accordingly
     *
     * @param   string|array<string>     $field Field(s) or Datfield(s) name to unset
     * @return  self
     */
    public function delete($field): self
    {
        $fields = (array)$field;

        foreach ($fields as $datfield) {
            if ($this->isDatField($datfield)) {
                ['field' => $property, 'path' => $path] = $this->parseDatField($datfield);
                $data = new Dot($this->get($property));
                if ($data->has($path)) {
                    $data->delete($path);
                  // Clears dirty state for this property as it could mess up in future
                    $this->setDirty($datfield, false);
                    $this->_set($property, $data->all());
                }
            } else {
                $this->unset($datfield);
            }
        }

        return $this;
    }

    /**
     * Returns whether this entity contains a field or a datfield named $field
     * that contains a non-null value.
     *
     * @param array<string>|string $field The field or fields to check.
     * @return bool
     */
    public function has($field): bool
    {
        foreach ((array)$field as $prop) {
            if ($this->isDatField($prop)) {
                ['field' => $f, 'path' => $path] = $this->parseDatField($prop);
                if (!$this->_has($f)) {
                    return false;
                }

                $f = new Dot($this->get($f));

                return $f->get($path) !== null;
            } elseif ($this->get($prop) === null) {
                  return false;
            }
        }

        return true;
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
            $parts = $this->parseDatField($field);
            $path = explode('.', $parts['path']);
            $data = $this->get($parts['field']);
            foreach ($path as $node) {
                $data = &$data[$node] ?? null;

                if ($data === null) {
                    break;
                }
            }
        } else {
            $data = $this->_get($field);
        }

        return $data;
    }

    /**
     * Checks if a field or a datfield is accessible
     *
     * For datfields, accessiblity can be granted at property level or field level
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
     * Set a value inside field JSON data or map to regular field
     *
     * @param   string    $field Dafield or array of [datfield => value]
     * @param   mixed           $value      Value to set
     * @param   array           $options    Options
     * @return  self
     */
    public function set($field, $value = null, array $options = []): self
    {
        // We need to rebuild options as in regular set
        if (!is_array($field) && !empty($field)) {
            $guard = false;
            $fields = [$field => $value];
        } else {
            $fields = $field;
            $guard = true;
            $options = (array)$value;
        }

        if (!is_array($fields)) {
            throw new \InvalidArgumentException('Cannot set an empty field');
        }

        $options += ['setter' => true, 'guard' => $guard];

        foreach ($fields as $datfield => $value) {
            if ($this->isDatField($datfield)) {
                $key = $this->_getDatFieldKey($datfield);

                if ($options['guard'] === true && !$this->isAccessible($key)) {
                    continue;
                }

                ['field' => $field, 'path' => $path] = $this->parseDatField($datfield);
                $fieldData = new Dot($this->get($field));
                $fieldData->set($path, $value);
                $this->setDirty($key, true);
                $this->_set($field, $fieldData->all(), $options);
            } else {
                $data = $this->_set($datfield, $value, $options);
            }
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
     * When procession a dat field, a checks will be done to ensure properties dirty state and field state are coherent
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
                    if (strpos($f, $field . '_') === 0) {
                        unset($this->_dirty[$f]);
                    }
                }
            }

            return $this->_setDirty($field, $isDirty);
        }

        $key = $this->_getDatFieldKey($field);
        ['field' => $field] = $this->parseDatField($field);

        if ($isDirty === false) {
            unset($this->_dirty[$key]);
            foreach ($this->_dirty as $f => $value) {
                if ($value === true && strpos($f, $field . '_') !== false) {
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
     * It will clears dirty state for the whole JSON field
     *
     * @param   string|array<string>     $field Field(s) or Datfield(s) name to unset
     * @return  self
     */
    public function unset($field): self
    {
        $fields = (array)$field;

        foreach ($fields as $field) {
            if ($this->isDatField($field)) {
                ['field' => $property, 'path' => $path] = $this->parseDatField($field);
                $data = new Dot($this->get($property));
                $data->delete($path);
                $this->_set($property, $data->all());
                $this->setDirty($property, false);
            } else {
                unset($this->_fields[$field], $this->_original[$field], $this->_dirty[$field]);
            }
        }

        return $this;
    }
}
