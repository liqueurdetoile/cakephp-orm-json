<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Expression;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Lqdt\OrmJson\DatField\DatFieldParserTrait;

/**
 * Extends QueryExpression in order to allow storage of
 * initially used datfield to handle correctly json types for
 * already parsed datfield
 */
class DatFieldExpression extends QueryExpression
{
    use DatFieldParserTrait;

    /**
     * Stores datfield
     *
     * @var string
     */
    protected $_datfield;

    /**
     * Stores parsed datfield key
     *
     * @var string
     */
    protected $_datfieldKey;

    /**
     * Get the datfield string linked to this expression
     *
     * @return string|null
     */
    public function getDatField(): ?string
    {
        return $this->_datfield;
    }

    /**
     * Get the datfield key based on stored datfield
     *
     * @return string|null
     */
    public function getDatFieldKey(): ?string
    {
        return $this->_datfieldKey;
    }

    /**
     * Sets the datfield linked to this expression
     *
     * @param  string $datfield  Datfield
     * @return self
     */
    public function setDatField(string $datfield): self
    {
        $this->_datfield = $datfield;
        $this->_datfieldKey = $this->_getDatFieldKey($datfield);

        return $this;
    }

    /**
     * Converts expression to a string through SQL parsing
     * It is meant to be used only for identifiers
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->sql(new ValueBinder());
    }
}
