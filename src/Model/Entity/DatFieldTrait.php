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
    /**
     * Test
     *
     * @param array $properties  Properties
     * @param array $options     Options
     */
    public function __construct(array $properties = [], array $options = [])
    {
        parent::__construct($properties, $options);

        // Process foreign keys and expose it as entity property to allow ORM EagerLoder to link data
        $repository = TableRegistry::getTableLocator()->get($this->getSource());
        if (
            $repository->hasBehavior('Datfield')
            || $repository->hasBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior')
        ) {
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
     * Get a value inside field JSON data. Returned value is cast to object unless
     * `$assoc` parameter is set to false
     *
     * @param   string  $datfield Datfield
     * @return  mixed             Field value (by reference)
     */
    public function &jsonGet(string $datfield)
    {
        if (DatField::isDatField($datfield)) {
            $parts = DatField::getDatFieldParts($datfield, $this->getSource());
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

        if (DatField::isDatField($datfield)) {
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
     *
     * @param   string    $datfield Datfield or regular field
     * @return  bool                `true` if key exists
     */
    public function jsonIsset(string $datfield): bool
    {
        if (DatField::isDatField($datfield)) {
            $parts = DatField::getDatFieldParts($datfield, $this->getSource());
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
            if (DatField::isDatField($datfield)) {
                $parts = DatField::getDatFieldParts($datfield, $this->getSource());
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
