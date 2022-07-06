<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM;

use Cake\ORM\Entity;
use Lqdt\OrmJson\Utility\DatField;

class ObjectEntity extends Entity
{
    use \Lqdt\OrmJson\Model\Entity\DatFieldTrait;

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
        DatField::isDatField($field) ? $this->jsonSet($field, $value) : $this->set($field, $value);
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
        return DatField::isDatField($field) ? $this->jsonIsset($field) : $this->has($field);
    }

    /**
     * Removes a field from this entity
     *
     * @param string $field The field to unset
     * @return void
     */
    public function __unset(string $field): void
    {
        DatField::isDatField($field) ? $this->jsonUnset($field) : $this->unset($field);
    }
}
