<?php
namespace Lqdt\OrmJson\Model\Entity;

use Adbar\Dot;

/**
 * This trait adds useful methods to get and set values in JSON fields
 * @version 1.0.0
 * @since   1.0.0
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
trait JsonTrait
{
    /**
     * Parsed field name
     * @var string
     */
    private $_field;

    /**
     * Parsed JSON path
     * @var string
     */
    private $_path;

    /**
     * field JSON data
     * @var Dot
     */
    private $_data;

    /**
     * Parse a datfield and populates private properties
     * @method  _parse
     * @version 1.0.0
     * @since   1.0.0
     * @param   string    $datfield Datfield name notation
     */
    private function _parse(string $datfield) : void
    {
        $parts = explode('@', $datfield);
        if (empty($parts[1])) {
            $this->_field = (string) $parts[0];
            $this->_path = null;
            $this->_data = dot($this->get($this->_field));
            return;
        }
        $this->_field = (string) $parts[1];
        $this->_path = (string) $parts[0];
        $this->_data = dot($this->get($this->_field));
        return;
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
        $this->_parse($datfield);
        if ($assoc) {
            return $this->_data->get($this->_path); // returns array
        }
        // returns object
        return json_decode($this->_data->toJson($this->_path));
    }

    /**
     * Set a value inside field JSON data
     * @method  jsonSet
     * @version 1.0.0
     * @since   1.0.0
     * @param   string    $datfield Dafield
     * @param   mixed     $value    [description]
     * @return  self                [description]
     */
    public function jsonSet($datfield, $value = null) : self
    {
        if (is_array($datfield)) {
            foreach ($datfield as $field => $value) {
                $this->jsonSet($field, $value);
            }
            return $this;
        }

        $this->_parse($datfield);
        $this->_data->set($this->_path, $value);
        $this->set($this->_field, $this->_data->all());
        return $this;
    }

    /**
     * Check if a key exists within path
     * @method  jsonIsset
     * @version 1.0.0
     * @since   1.0.0
     * @param   string    $datfield Datfield
     * @return  bool                `true` if key exists
     */
    public function jsonIsset(string $datfield) : bool
    {
        $this->_parse($datfield);
        return $this->_data->has($this->_path);
    }

    /**
     * Delete a key from JSON data
     * @method  jsonUnset
     * @version 1.0.0
     * @since   1.0.0
     * @param   string|array     $datfield Datfield
     * @return  self                       [description]
     */
    public function jsonUnset($datfield) : self
    {
        if (is_array($datfield)) {
            foreach ($datfield as $field) {
                $this->jsonUnset($field);
            }
            return $this;
        }

        $this->_parse($datfield);
        $this->_data->delete($this->_path);
        $this->set($this->_field, $this->_data->all());
        return $this;
    }
}
