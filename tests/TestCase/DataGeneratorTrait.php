<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase;

/**
 * This trait brings up a data generator as `data` property
 */
trait DataGeneratorTrait
{
    public $data = null;

    public function __construct()
    {
        parent::__construct();

        $this->data = new DataGenerator();
    }
}
