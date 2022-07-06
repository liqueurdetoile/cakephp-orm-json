<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Database\Dialect;

use Cake\Database\Query;
use Lqdt\OrmJson\Utility\DatField;

trait DatFieldMysqlSelectTrait
{
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
        if (DatField::isDatField($alias)) {
            $alias = strtolower(DatField::buildAliasFromTemplate(
                $field,
                $config['jsonPropertyTemplate'],
                $config['jsonSeparator'],
                $repository->getAlias()
            ));
        }

        // Protect reserved keywords alias
        $qalias = $this->_startQuote . $alias . $this->_endQuote;
        $fields[$qalias] = DatField::jsonFieldName($field, false, $repository->getAlias());
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
        if (DatField::isDatField($alias)) {
            $alias = strtolower(DatField::buildAliasFromTemplate(
                $field,
                '{{field}}{{separator}}{{path}}',
                '__',
                $repository->getAlias()
            ));
        } else {
            $alias = str_replace('.', '__', $alias);
        }

        $fields[$alias] = DatField::jsonFieldName($field, false, $repository->getAlias());
        $types[$alias] = 'json';
        $query->getSelectTypeMap()->setTypes($types);

        return $fields;
    }

    /**
     * Apply datfieldnotation to select statements
     *
     * @param  array $fields               Selected fields
     * @param \Cake\Database\Query $query Current query
     * @return array Updated selected field
     */
    protected function _selectedFieldsConverter(array $fields, Query $query): array
    {
        $repository = $query->getRepository();
        $haveDatFields = false;
        $filteredFields = [];
        $updatedFields = [];

        foreach ($fields as $alias => $field) {
            // Do not process SELECT expressions
            if (!is_string($field)) {
                $updatedFields[$alias] = $field;
                continue;
            }

            if (DatField::isDatField($field)) {
                $fieldname = DatField::getDatFieldPart('field', $field, $repository->getAlias());
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
}
