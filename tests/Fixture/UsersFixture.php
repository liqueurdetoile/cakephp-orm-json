<?php
namespace Lqdt\OrmJson\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Users fixture
 */
class UsersFixture extends TestFixture
{
    public $connection = 'test';
    public $table = 'users';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'attributes' => ['type' => 'json', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []]
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Init method
     *
     * @return void
     */
    public function init()
    {
        $this->records = [
            [
                'id' => 1,
                'attributes' => [
                  'role_id' => 'fab2e42a-8ebc-4d01-bedf-fc8edb5e83a3'
                ]
            ],
            [
                'id' => 2,
                'attributes' => [
                  'role_id' => 'dfaadf18-2edd-46eb-aef9-64743882a2dd'
                ]
            ],
            [
                'id' => 3,
                'attributes' => []
            ]
        ];
        parent::init();
    }
}
