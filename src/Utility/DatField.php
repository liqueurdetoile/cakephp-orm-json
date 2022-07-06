<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Utility;

use Cake\Core\Exception\Exception;
use Mustache_Engine;

/**
 * Utility class to parse dat fields
 *
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
class DatField
{
    /**
     * Utility function to check if a field is datfield
     *
     * @param   string $field Field name
     * @return bool
     */
    public static function isDatField(?string $field = null): bool
    {
        return $field ? preg_match('/^[\w\.]+@[\w\.]+$/i', $field) === 1 : false;
    }

    /**
     * Parses a datfield into parts, optionnally using repository name
     *
     * @param  string      $datfield   Datfield
     * @param  string|null $repository Repository name
     * @return array
     */
    public static function getDatFieldParts(string $datfield, ?string $repository = null): array
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
          'path' => $path,
        ];
    }

    /**
     * Return the requested part in datfield among `model`, `field` and `path`
     *
     * @param  string      $part       Datfield part
     * @param  string      $datfield   Datfield
     * @param  string|null $repository Repository name
     * @return string
     */
    public static function getDatFieldPart(string $part, string $datfield, ?string $repository = null): string
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
          'path' => $path,
        ][$part];
    }

    /**
     * Convert a property name given under datfield format
     * into a valid JSON_EXTRACT short notation usable in cakePHP standard queries
     *
     * @param   string $datfield   Input field name
     * @param   bool   $unquote    If `true`, the value will also be unquoted through JSON_UNQUOTE
     * @param   string $repository If provided and if model is not already included in dat field, the repository name will be prepended to field name
     * @return  string        Returns Mysql valid formatted name to query JSON
     */
    public static function jsonFieldName(string $datfield, bool $unquote = false, ?string $repository = null): string
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
     * @param   string $expression SQL fragment to be reworked
     * @return  string             Parsed string that contains modified SQL fragment
     */
    public static function jsonFieldsNameinString(string $expression): string
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

    /**
     * Buils an alias from a template
     *
     * @param  string      $datField   Datefield
     * @param  string      $template   Template
     * @param  string      $separator  Separator
     * @param  string|null $repository Repository name
     * @return string             [description]
     */
    public static function buildAliasFromTemplate(
        string $datField,
        string $template,
        string $separator = '_',
        ?string $repository = null
    ): string {
        $parts = self::getDatFieldParts($datField, $repository);
        $parts['path'] = str_replace('.', $separator, $parts['path']);
        $parts['separator'] = $separator;
        $parts['sep'] = $separator;
        $mustache = new Mustache_Engine();

        return $mustache->render($template, $parts);
    }
}
