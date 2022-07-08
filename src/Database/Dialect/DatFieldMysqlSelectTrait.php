<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Dialect;

use Cake\Database\Query;

trait DatFieldMysqlSelectTrait
{
    /**
     * Apply datfieldnotation to select statements
     *
     * @param  array $fields               Selected fields
     * @param \Cake\Database\Query $query Current query
     * @return array Updated selected field
     */
    protected function translateSelect(array $fields, Query $query): array
    {
        $repository = $query->getRepository();
        $haveDatFields = false;
        $filteredFields = [];
        $updatedFields = [];

        foreach ($fields as $alias => $field) {
            if (!is_string($field)) {
                $updatedFields[$alias] = $field;
                continue;
            }

            if ($this->isDatField($field)) {
                $fieldname = $this->getDatFieldPart('field', $field, $repository->getAlias());
                $config = $repository->getJsonFieldConfig($fieldname);

                $updatedFields = $config['keepJsonNested']
                  ? $this->_filterJsonField($updatedFields, $alias, $field, $config, $query) :
                  $this->_extractJsonField($updatedFields, $alias, $field, $config, $query);
            } else {
                $updatedFields[$alias] = $field;
            }
        }

        return $updatedFields;
    }

    /**
     * Updates field selection to extract fields at entity level
     *
     * @param  array  $fields               Selected fields
     * @param  string $alias                Alias
     * @param  string  $field               Field name
     * @param  array  $config               Parsing configuration     *
     * @param \Cake\Database\Query $query Query
     * @return array  Updated fields selection
     */
    protected function _extractJsonField(
        array $fields,
        string $alias,
        string $field,
        array $config,
        Query $query
    ): array {
        $repository = $query->getRepository();
        $types = $query->getSelectTypeMap()->getTypes();

      // Transform field alias
        if ($this->isDatField($alias)) {
            $alias = strtolower($this->renderFromDatFieldAndTemplate(
                $field,
                $config['jsonPropertyTemplate'],
                $config['jsonSeparator'],
                $repository->getAlias()
            ));
        }

        // Protect reserved keywords alias
        $qalias = $this->_startQuote . $alias . $this->_endQuote;
        $fields[$qalias] = $this->translateToJsonExtract($field, false, $repository->getAlias());
        $types[$alias] = 'json';
        $query->getSelectTypeMap()->setTypes($types);

        return $fields;
    }

    /**
     * Converts select clause to parse only selected field in JSON structure or map to an arbitrary one
     *
     * @param  array  $fields               Selected fields
     * @param  string $alias                Alias
     * @param  string $field                Field name
     * @param  array  $config               Parsing configuration
     * @param \Cake\Database\Query $query query
     * @return array          Updated selected fields
     */
    protected function _filterJsonField(
        array $fields,
        string $alias,
        string $field,
        array $config,
        Query $query
    ): array {
        $repository = $query->getRepository();
        $types = $query->getSelectTypeMap()->getTypes();

        // Transform field alias
        if ($this->isDatField($alias)) {
            $alias = strtolower($this->renderFromDatFieldAndTemplate(
                $field,
                '{{field}}{{separator}}{{path}}',
                '__',
                $repository->getAlias()
            ));
        } else {
            $alias = str_replace('.', '__', $alias);
        }

        $fields[$alias] = $this->translateToJsonExtract($field, false, $repository->getAlias());
        $types[$alias] = 'json';
        $query->getSelectTypeMap()->setTypes($types);

        return $fields;
    }
}
