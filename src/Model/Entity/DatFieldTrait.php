<?php
declare(strict_types=1);

/**
 * JSON trait for cakePHP framework
 *
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
namespace Lqdt\OrmJson\Model\Entity;

use Adbar\Dot;
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
    use DatFieldAwareTrait;

    /**
     * Magic getter to access fields that have been set in this entity
     *
     * @param string $field Name of the field to access
     * @return mixed
     */
    public function &__get(string $field)
    {
        return $this->jsonGet($field);
    }

    /**
     * Magic setter to add or edit a field in this entity
     *
     * @param string $field The name of the field to set
     * @param mixed $value The value to set to the field
     * @return void
     */
    public function __set(string $field, $value): void
    {
        $this->isDatField($field) ? $this->jsonSet($field, $value) : $this->set($field, $value);
    }

    /**
     * Returns whether this entity contains a field named $field
     * and is not set to null.
     *
     * @param string $field The field to check.
     * @return bool
     * @see \Cake\ORM\Entity::has()
     */
    public function __isset(string $field): bool
    {
        return $this->isDatField($field) ? $this->jsonIsset($field) : $this->has($field);
    }

    /**
     * Removes a field from this entity
     *
     * @param string $field The field to unset
     * @return void
     */
    public function __unset(string $field): void
    {
        $this->isDatField($field) ? $this->jsonUnset($field) : $this->unset($field);
    }

    /**
     * Get a value inside field JSON data. Returned value is cast to object unless
     * `$assoc` parameter is set to false
     *
     * @param   string  $datfield Datfield
     * @return  mixed             Field value (by reference)
     */
    public function &jsonGet(string $datfield)
    {
        if ($this->isDatField($datfield)) {
            $parts = $this->parseDatField($datfield, $this->getSource());
            $path = explode('.', $parts['path']);
            $data = $this->get($parts['field']);
            foreach ($path as $node) {
                $data = &$data[$node] ?? null;

                if ($data === null) {
                    break;
                }
            }
            // $fieldData = new Dot($this->get($parts['field']));
            // $data = $fieldData[$parts['path']];//$fieldData->get($parts['path']);
        } else {
            $data = $this->get($datfield);
        }

        return $data;
    }

    /**
     * Set a value inside field JSON data
     *
     * @param   string|array    $datfield   Dafield or array of [datfield => value]
     * @param   mixed           $value      Value to set
     * @return  self
     */
    public function jsonSet($datfield, $value = null): self
    {
        if (is_array($datfield)) {
            foreach ($datfield as $field => $value) {
                $this->jsonSet($field, $value);
            }

            return $this;
        }

        if ($this->isDatField($datfield)) {
            $parts = $this->parseDatField($datfield, $this->getSource());
            $fieldData = new Dot($this->get($parts['field']));
            $fieldData->set($parts['path'], $value);
            $this->set($parts['field'], $fieldData->all());
        } else {
            $data = $this->set($datfield, $value);
        }

        return $this;
    }

    /**
     * Check if a key exists within path
     *
     * @param   string    $datfield Datfield or regular field
     * @return  bool                `true` if key exists
     */
    public function jsonIsset(string $datfield): bool
    {
        if ($this->isDatField($datfield)) {
            $parts = $this->parseDatField($datfield, $this->getSource());
            $fieldData = new Dot($this->get($parts['field']));

            return $fieldData->has($parts['path']);
        } else {
            return $this->has($datfield);
        }
    }

    /**
     * Removes a key or a list of keys from datfield. Regular fields can also be provided
     *
     * @param   string|array<string>     $datfield Datfield
     * @return  self
     */
    public function jsonUnset($datfield): self
    {
        $datfields = (array)$datfield;

        foreach ($datfields as $datfield) {
            if ($this->isDatField($datfield)) {
                $parts = $this->parseDatField($datfield, $this->getSource());
                $fieldData = new Dot($this->get($parts['field']));
                $fieldData->delete($parts['path']);
                $this->set($parts['field'], $fieldData->all());
            } else {
                $this->unset($datfield);
            }
        }

        return $this;
    }
}
