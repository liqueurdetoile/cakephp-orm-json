<?php
namespace Lqdt\Coj\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Lqdt\Coj\ORM\JsonQuery;

/**
 * This CakePHP behavior adds support to performs mysql queries into JSON fields
 *
 * The behavior adds a custom finder <tt>Model::find('json')</tt> that returns a JsonQuery instance
 *
 * You can use any construct similar to classic query building based on field/value array
 * or full query string. The array form automatically escape string type parameters.
 *
 * JSON field name must be called in specific format like this : <tt>path@[Model.]field</tt> and they will be turned
 * into JSON_EXTRACT short notation like <tt>[Model.]fieldname->"$.path"</tt>
 *
 * @version 1.0.0
 * @since   1.0.0
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
class JsonBehavior extends Behavior
{
    public function jsonQuery(Query $parentQuery = null) : JsonQuery
    {
        $table =  $this->getTable();
        return new JsonQuery($table->getConnection(), $table, $parentQuery);
    }

    /**
     * Custom finder Model::find('json') that lets use JSON inner field key selector
     * and JSON inner field key/value set for WHERE clause
     *
     * @method  findJson
     * @version 1.0.0
     * @since   1.0.0
     * @param   Query     $query   CakePHP Query Object
     * @param   array     $options Options for the finder<br>
     *  <tt>$options = [<br>
     *    'json.fields' => (array|string) Fields/key of JSON type<br>
     *    'json.conditions' => (array|string) Conditions to use in WHERE clause<br>
     *    ...<br>
     *  ]</tt>
     * @return  JsonQuery              Cunstom JsonQuery instance
     */
    public function findJson(Query $query, array $options) : JsonQuery
    {
        $query = $this->jsonQuery($query);

        if (!empty($options['json.fields'])) {
            $query->jsonselect((array) $options['json.fields']);
            unset($options['json.fields']);
        }

        if (!empty($options['json.conditions'])) {
            $query->jsonwhere((array) $options['json.conditions']);
            unset($options['json.conditions']);
        }

        return $query;
    }
}
