<?php
namespace Lqdt\OrmJson\Utility;

use Cake\Core\Exception\Exception;
use Mustache_Engine;

/**
 * Utility class to parse dat fields
 * @version 1.0.0
 * @since 1.6.0
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
class DatField
{
    /**
     * Utility function to check if a field is datfield
     * @version 1.0.0
     * @since   1.3.0
     * @param   string    $field Field name
     * @return  boolean
     */
    public static function isDatField(string $field = null) : bool
    {
        return preg_match('/^[\w\.]+@[\w\.]+$/i', $field) === 1;
    }

    public static function getDatFieldParts(string $datfield, string $repository = null) : array
    {
        $parts = explode('@', $datfield);
        $path = array_shift($parts);
        $field = array_shift($parts);
        $model = $repository;

        if (empty($path) || empty($field)) {
            throw new Exception('Lqdt/OrmJson : Datfield format is invalid (' . $datfield . ')');
        }

        // Check if repository is prepended to path
        $parts = explode('.', $path);
        if ($parts[0] === $repository) {
            $model = array_shift($parts);
            $path = implode('.', $parts);
        }

        // Check if repository is prepended to field
        $parts = explode('.', $field);
        if (count($parts) > 1) {
            $model = array_shift($parts);
            $field = implode('.', $parts);
        }

        return [
          'model' => $model,
          'field' => $field,
          'path' => $path
        ];
    }

    /**
     * Convert a property name given under datfield format
     * into a valid JSON_EXTRACT short notation usable in cakePHP standard queries
     *
     * @version 1.1.0
     * @since   1.0.0
     * @param   String      $datfield                       Input field name
     * @param   Bool        [$unquote=false]                If `true`, the value will also be unquoted through JSON_UNQUOTE
     * @param   String      [$repository=null]              If provided and if model is not already included in dat field, the repository name will be prepended to field name
     * @return  String      Returns Mysql valid formatted name to query JSON
     */
    public static function jsonFieldName(string $datfield, bool $unquote = false, string $repository = null) : string
    {
        try {
            ['model' => $model, 'field' => $field, 'path' => $path] = self::getDatFieldParts($datfield, $repository);
            $operator = $unquote ? '->>"$.' : '->"$.';
            $field = $repository ? implode('.', [$model, $field]) : $field;

            return $field . $operator . $path . '"';
        } catch (Exception $err) {
            return $datfield;
        }
    }

    /**
     * Apply jsonFieldName to every property name detected in a string, mainly used
     * to parse SQL fragments
     *
     * The regexp is a bit tricky to avoid collision with mail parameter value
     * that will be enclosed by quotes
     *
     * @version 1.0.0
     * @since   1.0.0
     * @param   string             $expression SQL fragment to be reworked
     * @return  string             Parsed string that contains modified SQL fragment
     */
    public static function jsonFieldsNameinString(string $expression) : string
    {
        return preg_replace_callback(
            '|([^\w]*)([\w\.]+@[\w\.]+)|i',
            function ($matches) {
                if (!preg_match('|[\'"]|', $matches[1])) {
                    return str_replace($matches[2], self::jsonFieldName($matches[2]), $matches[0]);
                }
                return $matches[0];
            },
            $expression
        );
    }

    public static function buildAliasFromTemplate(string $datField, string $template, string $separator = '_', string $repository = null) : string
    {
        $parts = self::getDatFieldParts($datField, $repository);
        $parts['path'] = str_replace('.', $separator, $parts['path']);
        $parts['separator'] = $separator;
        $parts['sep'] = $separator;
        $mustache = new Mustache_Engine;
        return $mustache->render($template, $parts);
    }
}
