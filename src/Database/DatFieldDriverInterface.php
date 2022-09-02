<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database;

use Cake\Database\DriverInterface;

interface DatFieldDriverInterface extends DriverInterface
{
    /**
     * Utility function to check if a field is datfield in driver
     * It must detect unstranslated and already translated datfield as well
     *
     * @param   mixed $datfield Field name
     * @return  int  0 if not a datfield, 1 for v1 notation, 2 for v2 notation, 3 for DatfieldExpression
     */
    public function isDatField($datfield): int;

    /**
     * Translates a datfield notation into a valid driver dependent SQL FunctionExpression that allows
     * to identify and target data into a JSON field.
     *
     * @param array|string|\Cake\Database\ExpressionInterface $datfield Datfield
     * @param  bool     $unquote                  If `true`, returned data should be unquoted
     * @param  ?string  $repository               Repository alias
     * @return array|string|\Cake\Database\ExpressionInterface
     */
    public function translateDatField(
        $datfield,
        bool $unquote = false,
        ?string $repository = null
    );

    /**
     * Translates a datfield notation into a valid driver dependent SQL snippet that allows
     * to identify and target data into a JSON field.
     *
     * @param string|\Lqdt\OrmJson\Database\Expression\DatFieldExpression $datfield Datfield
     * @param  bool     $unquote                  If `true`, returned data should be unquoted
     * @param  ?string  $repository               Repository alias
     * @return string
     */
    public function translateDatFieldAsSql(
        $datfield,
        bool $unquote = false,
        ?string $repository = null
    ): string;
}
