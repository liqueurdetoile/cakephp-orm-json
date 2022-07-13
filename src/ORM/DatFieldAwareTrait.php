<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM;

use Adbar\Dot;
use Mustache_Engine;

/**
 * Utility class to parse dat fields
 *
 * @license MIT
 * @author  Liqueur de Toile <contact@liqueurdetoile.com>
 */
trait DatFieldAwareTrait
{
    /**
     * Reads the datfield value in data. target data should not be nested
     *
     * @param  string $key Datfield
     * @param  array|\Lqdt\OrmJson\ORM\Entity $data Data
     * @return mixed
     */
    public function getDatFieldValueInData(string $key, $data)
    {
        // Regular field, simply map
        if (!$this->isDatField($key)) {
            return $data[$key];
        }

        if (is_array($data)) {
            ['field' => $field, 'path' => $path] = $this->parseDatField($key);
            $path = implode('.', [$field,$path]);
            $dot = new Dot($data);

            return $dot->get($path);
        } else {
            // Entity case, we can use getter
            return $data->{$key};
        }
    }

    /**
     * Return the requested part in datfield among `model`, `field` and `path`
     *
     * @param  string      $part       Datfield part
     * @param  string      $datfield   Datfield
     * @param  string|null $repository Repository name
     * @return string|null
     */
    public function getDatFieldPart(string $part, string $datfield, ?string $repository = null): ?string
    {
        if (!in_array($part, ['model', 'field', 'path'])) {
            throw new \Exception('Requested part in DatField is not valid');
        }

        $parsed = $this->parseDatField($datfield, $repository);

        return $parsed[$part];
    }

    /**
     * Utility function to check if a field is datfield and know its structure
     *
     * @param   string $field Field name
     * @return  int   0 for non datfield strings, 1 for path@field notation and 2 for field->path notation
     */
    public function isDatField(?string $field = null): int
    {
        if (!$field) {
            return 0;
        }

        if (preg_match('/^[\w\.]+(@|->)[\w\.]+$/i', $field) === 1) {
            return strpos($field, '@') !== false ? 1 : 2;
        }

        return 0;
    }

    /**
     * Parses a datfield into its parts, optionnally using repository name
     *
     * @param  string      $datfield   Datfield
     * @param  string|null $repository Repository name
     * @return array
     */
    public function parseDatField(string $datfield, ?string $repository = null): array
    {
        $type = $this->isDatField($datfield);

        if ($type === 0) {
            throw new \Exception('Lqdt/OrmJson : Datfield format is invalid (' . $datfield . ')');
        }

        return $type === 1 ?
          $this->_parseV1($datfield, $repository) :
          $this->_parseV2($datfield, $repository);
    }

    /**
     * Translates a datfield into a JSON_CONTAINS_PATH SQL fragment
     *
     * @param  string   $datfield                 Datfield
     * @param  ?string  $repository               Repository alias
     * @param  string   $mode                  If `true`, Json enquote will be used
     * @return string   SQL fragment
     */
    public function translateToJsonContainsPath(
        string $datfield,
        ?string $repository = null,
        string $mode = 'all'
    ): string {
        try {
            ['model' => $model, 'field' => $field, 'path' => $path] = $this->parseDatField($datfield, $repository);
            $field = $model ? implode('.', [$model, $field]) : $field;
            $path = implode('.', ['$', $path]);

            return "JSON_CONTAINS_PATH({$field}, '{$mode}', '{$path}')";
        } catch (\Exception $err) {
            return $datfield;
        }
    }

    /**
     * Translates a datfield into a JSON_EXTRACT SQL fragment
     *
     * @param  string   $datfield                 Datfield
     * @param  bool     $unquote                  If `true`, Json enquote will be used
     * @param  ?string  $repository               Repository alias
     * @return string   SQL fragment
     */
    public function translateToJsonExtract(string $datfield, bool $unquote = false, ?string $repository = null): string
    {
        try {
            ['model' => $model, 'field' => $field, 'path' => $path] = $this->parseDatField($datfield, $repository);
            $operator = $unquote ? '->>"$.' : '->"$.';
            $field = $model ? implode('.', [$model, $field]) : $field;

            return $field . $operator . $path . '"';
        } catch (\Exception $err) {
            return $datfield;
        }
    }

    /**
     * Parses all datfields in a SQL string to JSON_EXTRACT and returns it
     *
     * @param  string  $expression               Expression to parse
     * @param  bool     $unquote                  If `true`, Json enquote will be used
     * @param  ?string  $repository               Repository alias
     * @return string  Updated expression
     */
    public function translateSQLToJsonExtract(
        string $expression,
        bool $unquote = false,
        ?string $repository = null
    ): string {
        return preg_replace_callback(
            '/[\w\.]+(@|->)[\w\.]+/i',
            function ($matches) use ($unquote, $repository) {
                return $this->translateToJsonExtract($matches[0], $unquote, $repository);
            },
            $expression
        );
    }

    /**
     * Buils an alias from a template
     *
     * @param  string      $datfield   Datfield
     * @param  string      $template   Template
     * @param  string      $separator  Separator
     * @param  string|null $repository Repository name
     * @return string             [description]
     */
    public function renderFromDatFieldAndTemplate(
        string $datfield,
        string $template,
        string $separator = '_',
        ?string $repository = null
    ): string {
        $parts = $this->parseDatField($datfield, $repository);
        $parts['path'] = str_replace('.', $separator, $parts['path']);
        $parts['separator'] = $separator;
        $parts['sep'] = $separator;
        $mustache = new Mustache_Engine();

        return $mustache->render($template, $parts);
    }

    /**
     * Returns the key to store field state in entity as both V1 and V2 notation can be used
     *
     * @param  string|null $datfield   Datfield
     * @return string|null Key for datfield
     */
    protected function _getDatFieldKey(?string $datfield): ?string
    {
        return $this->isDatField($datfield) ?
          $this->renderFromDatFieldAndTemplate($datfield, '{{field}}{{separator}}{{path}}') :
          $datfield;
    }

    /**
     * Parses V1 datfields (path@field)
     *
     * @param  string $datfield                 Datfield to parse
     * @param  string|null $repository               Repository name
     * @return array  Datfield parts
     */
    protected function _parseV1(string $datfield, ?string $repository): array
    {
        $parts = explode('@', $datfield);
        $path = array_shift($parts);
        $field = array_shift($parts);

        if (empty($path) || empty($field)) {
            throw new \Exception('Lqdt/OrmJson : Datfield format is invalid (' . $datfield . ')');
        }

        return $this->_parse($field, $path, $repository);
    }

    /**
     * Parses V1 datfields (field->path)
     *
     * @param  string $datfield                      Datfield to parse
     * @param  ?string $repository               Repository name
     * @return array  Datfield parts
     */
    protected function _parseV2(string $datfield, ?string $repository): array
    {
        $parts = explode('->', $datfield);
        $field = array_shift($parts);
        $path = array_shift($parts);

        if (empty($path) || empty($field)) {
            throw new \Exception('Lqdt/OrmJson : Datfield format is invalid (' . $datfield . ')');
        }

        return $this->_parse($field, $path, $repository);
    }

    /**
     * Parses field and path from parts
     *
     * @param  string  $field Model/field part
     * @param  string  $path  Dotted path part
     * @param  ?string $repository  Repository alias
     * @return array  parsed parts of datfield
     */
    protected function _parse(string $field, string $path, ?string $repository): array
    {
        $model = $repository;

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
            $field = array_shift($parts);
        }

        return [
          'model' => $model,
          'field' => $field,
          'path' => $path,
        ];
    }
}
