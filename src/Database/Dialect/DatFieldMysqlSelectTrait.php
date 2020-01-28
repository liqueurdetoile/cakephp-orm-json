<?php
namespace Lqdt\OrmJson\Database\Dialect;

use Adbar\Dot;
use Cake\Database\Query;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Entity;
use Lqdt\OrmJson\Utility\DatField;

trait DatFieldMysqlSelectTrait
{
    protected function _decorateSelectedDatFields(array $row, array $filteredFields) : array
    {
        foreach ($filteredFields as $field => $attributes) {
            $data = new Dot($row[$field]);
            $filteredData = new Dot();

            foreach ($attributes as $attribute) {
                $filteredData->set($attribute, $data->get($attribute));
            }

            $row[$field] = $filteredData->all();
        }

        return $row;
    }

    protected function _decorateExtractedDatFields($row, string $separator, bool $keepNestedOnExtract)
    {
        if ($separator !== '\.') {
            return $row;
        }

        if ($row instanceof Entity) {
            $entityClass = get_class($row);
            $data = $row->toArray();
        } else {
            $data = $row;
        }

        $res = new Dot();

        foreach ($data as $key => $value) {
            $key = str_replace('\.', '.', $key);
            $res->set($key, $value);
        }

        $res = $keepNestedOnExtract ? $res->all() : $res->flatten();
        return $row instanceof Entity ? new $entityClass($res) : $res;
    }

    protected function _selectRegularField(string $field, string $alias, array $fields) : array
    {
        $fields[$alias] = $field;

        return $fields;
    }

    protected function _selectDatField(string $datfield, array $fields, array &$filteredFields, Query $query) : array
    {
        $repository = $query->getRepository();

        $alias = DatField::buildAliasFromTemplate($datfield, '{{model}}__{{field}}', '', $repository->getAlias());
        $target = DatField::buildAliasFromTemplate($datfield, '{{model}}.{{field}}', '', $repository->getAlias());
        $path = DatField::buildAliasFromTemplate($datfield, '{{path}}', '.', $repository->getAlias());

        // Add json field
        $types = $query->getSelectTypeMap()->getTypes();
        if (!array_key_exists($alias, $types)) {
            $types[$alias] = 'json';
            $query->getSelectTypeMap()->setTypes($types);
            $fields[$alias] = $target;
        }

        // Register json path in filtered fields
        $filtered = $filteredFields[$alias] ?? [];
        $filtered[] = $path;
        $filteredFields[$alias] = $filtered;

        return $fields;
    }

    protected function _extractDatField(string $datfield, string $alias, array $fields, Query $query) : array
    {
        $repository = $query->getRepository();
        $types = $query->getSelectTypeMap()->getTypes();

        // Transform field alias
        if (Datfield::isDatField($alias)) {
            $alias = DatField::buildAliasFromTemplate($datfield, $repository->getExtractAliasTemplate(), $repository->getExtractAliasSeparator(), $repository->getAlias());
        } else {
            // Protect dot in alias
            $alias = str_replace('.', '\.', $alias);
            $repository->setExtractAliasSeparator('\.');
        }

        if ($repository->isExtractAliasLowercased()) {
            $alias = strtolower($alias);
        }

        $fields[$alias] = DatField::jsonFieldName($datfield, false, $repository->getAlias());
        $types[$alias] = 'json';
        $query->getSelectTypeMap()->setTypes($types);

        return $fields;
    }

    protected function _selectedFieldsConverter(array $fields, Query $query) : array
    {
        $repository = $query->getRepository();
        $extractOnSelect = $repository->getExtractOnSelect();
        $keepNestedOnExtract = $repository->getKeepNestedOnExtract();

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
                $haveDatFields = true;

                if ($extractOnSelect) {
                    $updatedFields = $this->_extractDatField($field, $alias, $updatedFields, $query);
                } else {
                    $updatedFields = $this->_selectDatField($field, $updatedFields, $filteredFields, $query);
                }
            } else {
                $updatedFields = $this->_selectRegularField($field, $alias, $updatedFields);
            }
        }

        if ($haveDatFields) {
            if ($extractOnSelect) {
                $separator = $repository->getExtractAliasSeparator();
                $query->mapReduce(function ($row, $key, $mapReduce) use ($separator, $keepNestedOnExtract) {
                    $mapReduce->emit($this->_decorateExtractedDatFields($row, $separator, $keepNestedOnExtract));
                });
            } else {
                $query->decorateResults(function ($row) use ($filteredFields) {
                    return $this->_decorateSelectedDatFields($row, $filteredFields);
                });
            }
        }

        return $updatedFields;
    }
}
