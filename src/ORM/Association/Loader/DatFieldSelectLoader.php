<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM\Association\Loader;

use Cake\ORM\Association;
use Cake\ORM\Association\Loader\SelectLoader;
use Cake\ORM\Query;
use Lqdt\OrmJson\ORM\DatFieldAwareTrait;

class DatFieldSelectLoader extends SelectLoader
{
    use DatFieldAwareTrait;

    /**
     * @inheritDoc
     */
    protected function _buildResultMap(Query $fetchQuery, array $options): array
    {
        $resultMap = [];
        $singleResult = in_array($this->associationType, [Association::MANY_TO_ONE, Association::ONE_TO_ONE], true);
        $keys = in_array($this->associationType, [Association::ONE_TO_ONE, Association::ONE_TO_MANY], true) ?
            $this->foreignKey :
            $this->bindingKey;
        $key = (array)$keys;

        foreach ($fetchQuery->all() as $result) {
            $values = [];
            foreach ($key as $k) {
                // use datfield reader function to allow parsing of datfield primary keys
                $values[] = $this->getDatFieldValueInData($k, $result);
            }
            if ($singleResult) {
                $resultMap[implode(';', $values)] = $result;
            } else {
                $resultMap[implode(';', $values)][] = $result;
            }
        }

        return $resultMap;
    }
}
