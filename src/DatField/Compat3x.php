<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\DatField;

class Compat3x
{
    /**
     * Enables Cakephp 3.x compatibility by aliasing some classes that have been renamed in CakePHP 4.x
     *
     * It must be called at bootstrap step
     *
     * @return void
     */
    public static function enable(): void
    {
        $classes = [
          '\Cake\Database\Expression\Comparison' => '\Cake\Database\Expression\ComparisonExpression',
          '\Cake\Database\Type' => '\Cake\Database\TypeFactory',
          '\Cake\Core\Exception\Exception' => '\Cake\Core\Exception\CakeException',
        ];

        foreach ($classes as $old => $new) {
            if (!class_exists($new)) {
                class_alias($old, $new);
            }
        }
    }
}
