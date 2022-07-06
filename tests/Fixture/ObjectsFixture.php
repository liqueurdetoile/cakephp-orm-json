<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class ObjectsFixture extends TestFixture
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
                'id' => '993ce30f-01dd-46e9-ad75-4fc104e42c70',
                'attributes' => [
                  'username' => 'test1',
                  'email' => 'test1@liqueurdetoile.com',
                  'group' => 1,
                  'string' => 'string1',
                  'integer' => 10,
                  'boolean' => true,
                  'null' => null,
                  'decimal' => 1.2,
                  'float' => 1.2E+5,
                  'array' => [
                    'a',
                    'b',
                  ],
                  'object' => [
                    'a' => 'a',
                    'b' => 'b',
                  ],
                  'deep' => [
                      'key' => 'deepkey1',
                  ],
                ],
            ],
            [
                'id' => '993ce30f-01dd-46e9-ad75-4fc104e42c71',
                'attributes' => [
                  'username' => 'test2',
                  'email' => 'test2@liqueurdetoile.com',
                  'group' => 1,
                  'string' => 'astring2',
                  'integer' => 100,
                  'boolean' => false,
                ],
            ],
            [
                'id' => '993ce30f-01dd-46e9-ad75-4fc104e42c72',
                'attributes' => [
                  'username' => 'test3',
                  'group' => 2,
                  'email' => 'test3@liqueurdetoile.com',
                  'boolean' => false,
                  'null' => false,
                  'float' => 1.2E+15,
                  'deep' => [
                      'key' => 'deepkey2',
                  ],
                ],
            ],
        ];
        parent::init();
    }
}
