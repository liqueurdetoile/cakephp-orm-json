<?php
namespace Lqdt\OrmJson\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Sort objects fixture
 */
class SortObjectsFixture extends TestFixture
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
                'id' => '1',
                'attributes' => [
                  "string" => 'mid',
                  "same" => "a",
                  "integer" => 10,
                  "boolean" => true,
                  "decimal" => 2.4,
                  "float" => 1.2E+5,
                  "maybeNull" => "notnull",
                  "deep" => [
                      "key" => "mid"
                  ]
                ]
            ],
            [
                'id' => '2',
                'attributes' => [
                  "string" => 'xbottom',
                  "same" => "a",
                  "integer" => 50,
                  "boolean" => true,
                  "decimal" => 3.6,
                  "float" => 1.2E+10,
                  "maybeNull" => "xnotnull",
                  "deep" => [
                      "key" => "xbottom"
                  ]
                ]
            ],
            [
                'id' => '3',
                'attributes' => [
                  "string" => 'atop',
                  "same" => "b",
                  "integer" => 1,
                  "boolean" => false,
                  "decimal" => 1.2,
                  "float" => 1.2E+2,
                  "maybeNull" => null,
                  "deep" => [
                      "key" => "atop"
                  ]
                ]
            ]
        ];
        parent::init();
    }
}
