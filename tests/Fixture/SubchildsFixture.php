<?php
namespace Lqdt\OrmJson\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Profiles Fixture
 */
class SubchildsFixture extends TestFixture
{
    public $connection = 'test';
    public $table = 'subchilds';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
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
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e20',
                'attributes' => [
                  'child_id' => '26145a1-37e6-471f-83eb-ac5a253f4e10',
                  'name' => 'subchild1',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e21',
                'attributes' => [
                  'child_id' => '26145a1-37e6-471f-83eb-ac5a253f4e10',
                  'name' => 'subchild2',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e22',
                'attributes' => [
                  'child_id' => '26145a1-37e6-471f-83eb-ac5a253f4e11',
                  'name' => 'subchild3',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e23',
                'attributes' => [
                  'child_id' => '26145a1-37e6-471f-83eb-ac5a253f4e12',
                  'name' => 'subchild4',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e24',
                'attributes' => [
                  'child_id' => '26145a1-37e6-471f-83eb-ac5a253f4e13',
                  'name' => 'subchild5',
                ]
            ],
        ];
        parent::init();
    }
}
