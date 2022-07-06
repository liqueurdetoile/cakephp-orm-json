<?php
namespace Lqdt\OrmJson\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Profiles Fixture
 */
class ChildsFixture extends TestFixture
{
    public $connection = 'test';
    public $table = 'childs';
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
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e10',
                'attributes' => [
                  'parent_id' => '26145a1-37e6-471f-83eb-ac5a253f4e00',
                  'name' => 'child1',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e11',
                'attributes' => [
                  'parent_id' => '26145a1-37e6-471f-83eb-ac5a253f4e00',
                  'name' => 'child2',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e12',
                'attributes' => [
                  'parent_id' => '26145a1-37e6-471f-83eb-ac5a253f4e01',
                  'name' => 'child3',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e13',
                'attributes' => [
                  'parent_id' => '26145a1-37e6-471f-83eb-ac5a253f4e00',
                  'name' => 'child4',
                ]
            ],
        ];
        parent::init();
    }
}
