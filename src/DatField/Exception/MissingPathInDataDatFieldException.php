<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\DatField\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Used when a behavior cannot be found.
 */
class MissingPathInDataDatFieldException extends CakeException
{
    /**
     * @var string
     */
    protected $_messageTemplate = 'Unable to resolve datfield %s in provided data : missing %s node';
}
