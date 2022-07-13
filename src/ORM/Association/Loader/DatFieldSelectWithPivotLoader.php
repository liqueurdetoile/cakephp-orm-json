<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM\Association\Loader;

use Cake\ORM\Association\Loader\SelectWithPivotLoader;
use Cake\ORM\Query;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

class DatFieldSelectWithPivotLoader extends SelectWithPivotLoader
{
    use DatFieldAwareTrait;

    /**
     * @inheritDoc
     */
    protected function _buildResultMap(Query $fetchQuery, array $options): array
    {
        $resultMap = [];
        $key = (array)$options['foreignKey'];

        foreach ($fetchQuery->all() as $result) {
            if (!isset($result[$this->junctionProperty])) {
                throw new RuntimeException(sprintf(
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
