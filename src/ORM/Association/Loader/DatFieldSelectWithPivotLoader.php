<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM\Association\Loader;

use Cake\ORM\Association\Loader\SelectWithPivotLoader;
use Lqdt\OrmJson\DatField\DatFieldParserTrait;

class DatFieldSelectWithPivotLoader extends SelectWithPivotLoader
{
    use DatFieldParserTrait;

    /**
     * @inheritDoc
     */
    protected function _buildResultMap($fetchQuery, $options): array
    {
        $resultMap = [];
        $key = (array)$options['foreignKey'];

        // We must fetch the right alias for foreign key to match data correctly when key is selected
        $select = array_flip($fetchQuery->clause('select'));
        $key = array_map(function ($k) use ($fetchQuery, $select) {
            if ($this->isDatField($k)) {
                if (empty($this->getDatFieldPart('model', $k))) {
                    $k = $fetchQuery->getRepository()->getAlias() . '.' . $k;
                }

                if (is_int($select[$k] ?? false)) {
                    $select[$k] = $this->aliasDatField($k);
                }
            }

            return $select[$k] ?? $k;
        }, $key);

        foreach ($fetchQuery->all() as $result) {
            if (!isset($result[$this->junctionProperty])) {
                throw new \RuntimeException(sprintf(
                    '"%s" is missing from the belongsToMany results. Results cannot be created.',
                    $this->junctionProperty
                ));
            }

            $values = [];
            foreach ($key as $k) {
                $values[] = $this->getDatFieldValueInData($k, $result[$this->junctionProperty]);
            }
            $resultMap[implode(';', $values)][] = $result;
        }

        return $resultMap;
    }
}
