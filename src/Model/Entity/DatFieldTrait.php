<?php
/**
 * JSON trait for cakePHP framework
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
namespace Lqdt\OrmJson\Model\Entity;

use Adbar\Dot;
use Cake\ORM\TableRegistry;
use Lqdt\OrmJson\Utility\DatField;

/**
 * This trait adds useful methods to get and set values in JSON fields
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
    public function __construct(array $properties = [], array $options = [])
    {
        parent::__construct($properties, $options);

        // Process foreign keys and expose it as entity property to allow ORM EagerLoder to link data
        $repository = TableRegistry::getTableLocator()->get($this->getSource());
        if ($repository->hasBehavior('Datfield') || $repository->hasBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior')) {
            $keys = $repository->getForeignKeys();
            $datprops = [];
            foreach ($keys as $key) {
                extract($key);
                $value = (new Dot($properties[$field]))->get($path);
                $this[$property] = $value;
                $datprops[] = $property;
            }
            $this->setHidden($datprops, true);
        }
    }

    /**
     * Get a value inside field JSON data
     * @method  jsonGet
     * @version 1.0.0
     * @since   1.0.0
     * @param   string    $datfield Datfield
     * @param   boolean   $assoc    Returns associative array if true instead of an object
     * @return  mixed               Field value
     */
    public function jsonGet(string $datfield, bool $assoc = false)
    {
        if (Datfield::isDatField($datfield)) {
            $parts = DatField::getDatFieldParts($datfield, $this->getSource());
            $fieldData = new Dot($this->get($parts['field']));
            $data = $fieldData->get($parts['path']);
        } else {
            $data = $this->get($datfield);
        }

        return $assoc ? $data : json_decode(json_encode($data));
    }

    /**
     * Set a value inside field JSON data
     * @method  jsonSet
     * @version 1.0.0
     * @since   1.0.0
     * @param   string|array    $datfield   Dafield or array of [datfield => value]
     * @param   mixed           $value      Value to set
     * @return  self
     */
    public function jsonSet($datfield, $value = null) : self
    {
        if (is_array($datfield)) {
            foreach ($datfield as $field => $value) {
                $this->jsonSet($field, $value);
            }
            return $this;
        }

        if (Datfield::isDatField($datfield)) {
            $parts = DatField::getDatFieldParts($datfield, $this->getSource());
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
     * @method  jsonIsset
     * @version 1.0.0
     * @since   1.0.0
     * @param   string    $datfield Datfield or regular field
     * @return  bool                `true` if key exists
     */
    public function jsonIsset(string $datfield) : bool
    {
        if (Datfield::isDatField($datfield)) {
            $parts = DatField::getDatFieldParts($datfield, $this->getSource());
            $fieldData = new Dot($this->get($parts['field']));
            return $fieldData->has($parts['path']);
        } else {
            return $this->has($datfield);
        }
    }

    /**
     * Delete a key from JSON data
     * @method  jsonUnset
     * @version 1.0.0
     * @since   1.0.0
     * @param   string|array     $datfield Datfield
     * @return  self
     */
    public function jsonUnset($datfield) : self
    {
        if (is_array($datfield)) {
            foreach ($datfield as $field) {
                $this->jsonUnset($field);
            }
            return $this;
        }

        if (Datfield::isDatField($datfield)) {
            $parts = DatField::getDatFieldParts($datfield, $this->getSource());
            $fieldData = new Dot($this->get($parts['field']));
            $fieldData->delete($parts['path']);
            $this->set($parts['field'], $fieldData->all());
        } else {
            $this->unsetProperty($datfield);
        }

        return $this;
    }

    /**
     * Merge new json values into fields when called after patchEntity
     * @version 1.0.0
     * @since   1.1.0
     * @return  self
     */
    public function jsonMerge() : self
    {
        if (!empty($original = $this->getOriginalValues())) {
            foreach ($original as $field => $value) {
                if (is_array($value)) {
                    $this->$field = array_merge($value, $this->$field);
                }
            }
        }
        return $this;
    }
}
