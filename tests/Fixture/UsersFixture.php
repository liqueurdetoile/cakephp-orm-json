<?php
namespace Lqdt\Coj\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
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
                'id' => '1',
                'attributes' => [
                  "username" => "test1",
                  "email" => "test1@liqueurdetoile.com",
                  "string" => "string1",
                  "integer" => 10,
                  "boolean" => true,
                  "null" => null,
                  "decimal" => 1.2,
                  "float" => 1.2E+5,
                  "array" => [
                    'a',
                    'b'
                  ],
                  "object" => [
                    'a' => 'a',
                    'b' => 'b'
                  ],
                  "deep" => [
                      "key" => "deepkey1"
                  ]
                ]
            ],
            [
                'id' => '2',
                'attributes' => [
                  "username" => "test2",
                  "email" => "test2@liqueurdetoile.com",
                  "string" => "string2",
                  "integer" => 100,
                  "boolean" => false
                ]
            ],
            [
                'id' => '3',
                'attributes' => [
                  "username" => "test3",
                  "email" => "test3@liqueurdetoile.com",
                  "boolean" => false,
                  "null" => false,
                  "float" => 1.2E+15,
                  "deep" => [
                      "key" => "deepkey2"
                  ]
                ]
            ]
        ];
        parent::init();
    }
}
