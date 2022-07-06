<?php
namespace Lqdt\OrmJson\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Profiles Fixture
 */
class ParentsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e00',
                'attributes' => [
                  'name' => 'parent1',
                ]
            ],
            [
                'id' => '26145a1-37e6-471f-83eb-ac5a253f4e01',
                'attributes' => [
                  'name' => 'parent2',
                ]
            ]
        ];

        parent::init();
    }
}
