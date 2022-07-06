<?php
namespace Lqdt\OrmJson\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Profiles Fixture
 */
class ProfilesFixture extends TestFixture
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
                'id' => 1,
                'attributes' => [
                  'user_id' => 3
                ]
            ],
            [
                'id' => 2,
                'attributes' => [
                  'user_id' => 1
                ]
            ]
        ];
        parent::init();
    }
}
