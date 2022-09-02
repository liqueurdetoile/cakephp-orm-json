<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM\Association\Loader;

use Cake\ORM\Association;
use Cake\ORM\Association\Loader\SelectLoader;
use Cake\ORM\Query;
use InvalidArgumentException;
use Lqdt\OrmJson\DatField\DatFieldParserTrait;

class DatFieldSelectLoader extends SelectLoader
{
    use DatFieldParserTrait;

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

    /**
     * @inheritDoc
     */
    protected function _assertFieldsPresent(Query $fetchQuery, array $key): void
    {
        if ($fetchQuery->isAutoFieldsEnabled()) {
            return;
        }

        // We must override here as aliasFields broke datfield notation
        $select = $fetchQuery->aliasFields($fetchQuery->clause('select'));
        if (empty($select)) {
            return;
        }

        $missingKey = function ($fieldList, $key) {
            foreach ($key as $keyField) {
                if (!in_array($keyField, $fieldList, true)) {
                    return true;
                }
            }

            return false;
        };

        $missingFields = $missingKey($select, $key);
        if ($missingFields) {
            $driver = $fetchQuery->getConnection()->getDriver();
            $quoted = array_map([$driver, 'quoteIdentifier'], $key);
            $missingFields = $missingKey($select, $quoted);
        }

        if ($missingFields) {
            throw new InvalidArgumentException(
                sprintf(
                    'You are required to select the "%s" field(s)',
                    implode(', ', $key)
                )
            );
        }
    }
}
