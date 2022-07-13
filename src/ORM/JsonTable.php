<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\ORM;

use Cake\ORM\Table;
use Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo;
use Lqdt\OrmJson\ORM\Association\DatFieldBelongsToMany;
use Lqdt\OrmJson\ORM\Association\DatFieldHasMany;
use Lqdt\OrmJson\ORM\Association\DatFieldHasOne;

class JsonTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Lqdt\OrmJson\Model\Behavior\DatFieldBehavior');
    }

    /**
     * @inheritDoc
     */
    public function belongsTo(string $associated, array $options = []): DatFieldBelongsTo
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldBelongsTo $association */
        $association = $this->_associations->load(DatFieldBelongsTo::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function hasOne(string $associated, array $options = []): DatFieldHasOne
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldHasOne $association */
        $association = $this->_associations->load(DatFieldHasOne::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function hasMany(string $associated, array $options = []): DatFieldHasMany
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldHasMany $association */
        $association = $this->_associations->load(DatFieldHasMany::class, $associated, $options);

        return $association;
    }

    /**
     * @inheritDoc
     */
    public function belongsToMany(string $associated, array $options = []): DatFieldBelongsToMany
    {
        $options += ['sourceTable' => $this];

        /** @var \Lqdt\OrmJson\ORM\Association\DatFieldBelongsToMany $association */
        $association = $this->_associations->load(DatFieldBelongsToMany::class, $associated, $options);

        return $association;
    }
}
