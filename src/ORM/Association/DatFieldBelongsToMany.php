<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsToMany;
use Cake\Utility\Hash;
use Closure;
use Lqdt\OrmJson\ORM\Association\Loader\DatFieldSelectWithPivotLoader;

class DatFieldBelongsToMany extends BelongsToMany
{
    /**
     * @inheritDoc
     */
    public function eagerLoader(array $options): Closure
    {
        $name = $this->_junctionAssociationName();
        $loader = new DatFieldSelectWithPivotLoader([
            'alias' => $this->getAlias(),
            'sourceAlias' => $this->getSource()->getAlias(),
            'targetAlias' => $this->getTarget()->getAlias(),
            'foreignKey' => $this->getForeignKey(),
            'bindingKey' => $this->getBindingKey(),
            'strategy' => $this->getStrategy(),
            'associationType' => $this->type(),
            'sort' => $this->getSort(),
            'junctionAssociationName' => $name,
            'junctionProperty' => $this->_junctionProperty,
            'junctionAssoc' => $this->getTarget()->getAssociation($name),
            'junctionConditions' => $this->junctionConditions(),
            'finder' => function () {
                return $this->_appendJunctionJoin($this->find(), []);
            },
        ]);

        return $loader->buildEagerLoader($options);
    }

    /**
     * @inheritDoc
     */
    public function replaceLinks(EntityInterface $sourceEntity, array $targetEntities, array $options = []): bool
    {
        $bindingKey = (array)$this->getBindingKey();
        $primaryValue = $sourceEntity->extract($bindingKey);

        if (count(Hash::filter($primaryValue)) !== count($bindingKey)) {
            $message = 'Could not find primary key value for source entity';
            throw new \InvalidArgumentException($message);
        }

        return $this->junction()->getConnection()->transactional(
            function () use ($sourceEntity, $targetEntities, $primaryValue, $options) {
                $junction = $this->junction();
                $target = $this->getTarget();

                $foreignKey = (array)$this->getForeignKey();
                $assocForeignKey = (array)$junction->getAssociation($target->getAlias())->getForeignKey();

                $prefixedForeignKey = array_map([$junction, 'aliasField'], $foreignKey);
                $junctionPrimaryKey = (array)$junction->getPrimaryKey();
                $junctionQueryAlias = $junction->getAlias() . '__matches';

                $keys = $matchesConditions = [];
                foreach (array_merge($assocForeignKey, $junctionPrimaryKey) as $key) {
                    // We need to use field alias for mapping as full json field will not be loaded in inner SELECT
                    if ($this->isDatField($key)) {
                        $identifier = $junctionQueryAlias
                          . '.'
                          . $this->renderFromDatFieldAndTemplate($key, '{{field}}{{separator}}{{path}}', '_');
                    } else {
                        $identifier = $junctionQueryAlias . '.' . $key;
                    }
                    $aliased = $junction->aliasField($key);
                    $keys[$key] = $aliased;
                    $matchesConditions[$aliased] = new IdentifierExpression($identifier);
                }

                // Use association to create row selection
                // with finders & association conditions.
                $matches = $this->_appendJunctionJoin($this->find())
                    ->select($keys)
                    ->where(array_combine($prefixedForeignKey, $primaryValue));

                // Create a subquery join to ensure we get
                // the correct entity passed to callbacks.
                $existing = $junction->query()
                    ->from([$junctionQueryAlias => $matches])
                    ->innerJoin(
                        [$junction->getAlias() => $junction->getTable()],
                        $matchesConditions
                    );

                $jointEntities = $this->_collectJointEntities($sourceEntity, $targetEntities);
                $inserts = $this->_diffLinks($existing, $jointEntities, $targetEntities, $options);
                if ($inserts === false) {
                    return false;
                }

                if ($inserts && !$this->_saveTarget($sourceEntity, $inserts, $options)) {
                    return false;
                }

                $property = $this->getProperty();

                if (count($inserts)) {
                    $inserted = array_combine(
                        array_keys($inserts),
                        (array)$sourceEntity->get($property)
                    ) ?: [];
                    $targetEntities = $inserted + $targetEntities;
                }

                ksort($targetEntities);
                $sourceEntity->set($property, array_values($targetEntities));
                $sourceEntity->setDirty($property, false);

                return true;
            }
        );
    }
}
