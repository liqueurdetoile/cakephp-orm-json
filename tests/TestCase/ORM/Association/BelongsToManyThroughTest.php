<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\ORM\Association;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Fixture\DataGenerator;

class BelongsToManyThroughTest extends TestCase
{
    use \CakephpTestSuiteLight\Fixture\TruncateDirtyTables;

    public $Orders;
    public $OrdersProducts;
    public $Products;

    public $orders;
    public $details;
    public $products;

    public function setUp(): void
    {
        parent::setUp();

        $this->Orders = TableRegistry::get('Orders', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\DatfieldBehaviorTable',
          'table' => 'orders',
        ]);

        $this->OrdersProducts = TableRegistry::get('OrdersProducts', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\DatfieldBehaviorTable',
          'table' => 'orders_products',
        ]);

        $this->Products = TableRegistry::get('Products', [
          'className' => 'Lqdt\OrmJson\Test\Model\Table\DatfieldBehaviorTable',
          'table' => 'products',
        ]);

        $generator = new DataGenerator();

        // Generate orders
        $this->orders = $generator
          ->faker('id', 'uuid')
          ->faker('attributes.date', 'date')
          ->generate(5);

        $this->products = $generator
          ->clear()
          ->faker('id', 'uuid')
          ->faker('attributes.name', 'word')
          ->faker('attributes.price', 'randomElement', [10, 20, 50])
          ->generate(20);

        $this->details = $generator
          ->clear()
          ->faker('id', 'uuid')
          ->faker('attributes.order_id', 'randomElement', array_map(function ($order) {
            return $order['id'];
          }, $this->orders))
          ->faker('attributes.product_id', 'randomElement', array_map(function ($product) {
            return $product['id'];
          }, $this->products))
          ->faker('attributes.quantity', 'randomElement', [1,2,3,4,5])
          ->generate(50);

        $this->Orders->datFieldBelongsToMany('Products', [
          'foreignKey' => 'attributes->order_id',
          'targetForeignKey' => 'attributes->product_id',
          'through' => 'OrdersProducts',
          'dependent' => true,
        ]);

        $this->Products->datFieldBelongsToMany('Orders', [
          'foreignKey' => 'attributes->product_id',
          'targetForeignKey' => 'attributes->order_id',
          'through' => 'OrdersProducts',
          'dependent' => true,
        ]);

        $this->Orders->datFieldHasMany('OrdersProducts', [
          'foreignKey' => 'attributes->orders_id',
        ]);

        $this->Products->datFieldHasMany('OrdersProducts', [
          'foreignKey' => 'attributes->product_id',
        ]);

        $this->Orders->saveManyOrFail($this->Orders->newEntities($this->orders));
        $this->OrdersProducts->saveManyOrFail($this->OrdersProducts->newEntities($this->details));
        $this->Products->saveManyOrFail($this->Products->newEntities($this->products));
    }

    public function tearDown(): void
    {
        $this->Orders = null;
        $this->OrdersProducts = null;
        $this->Products = null;
        TableRegistry::clear();

        parent::tearDown();
    }

    public function testContain(): void
    {
        $orders = $this->Orders
          ->find()
          ->contain('Products')
          ->toArray();

        $this->assertNotEmpty($orders);
        foreach ($orders as $order) {
            $this->assertNotEmpty($order->products);
            foreach ($order->products as $product) {
                $this->assertEquals($order->id, $product->_joinData->{'attributes->order_id'});
                $this->assertEquals($product->id, $product->_joinData->{'attributes->product_id'});
                $this->assertNotEmpty($product->_joinData->{'attributes->quantity'});
            }
        }
    }

    public function testOrderedContain(): void
    {
        $orders = $this->Orders
          ->find()
          ->contain(['Products' => function ($q) {
              return $q->order(['OrdersProducts.attributes->quantity']);
          }])
          ->toArray();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $prev = null;
            $this->assertNotEmpty($order->products);
            foreach ($order->products as $product) {
                if ($prev !== null) {
                    $this->assertTrue($product->_joinData{'attributes->quantity'} <= $prev);
                    $prev = $product->_joinData{'attributes->quantity'};
                }
            }
        }
    }

    public function testFilteredContain(): void
    {
        $orders = $this->Orders
          ->find()
          ->contain('Products', function ($q) {
              return $q->where(['OrdersProducts.attributes->quantity >' => 3]);
          })
          ->toArray();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $this->assertNotEmpty($order->products);
            foreach ($order->products as $product) {
                $this->assertTrue($product->_joinData{'attributes->quantity'} > 3);
            }
        }
    }

    public function testMatching(): void
    {
        $orders = $this->Orders
          ->find()
          ->matching('Products', function ($q) {
              return $q->where(['OrdersProducts.attributes->quantity' => 2]);
          })
          ->toArray();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $this->assertEquals(2, $order->_matchingData['OrdersProducts']['attributes']['quantity']);
        }
    }

    public function testInnerJoinWith(): void
    {
        $orders = $this->Orders
          ->find()
          ->innerJoinWith('Products', function ($q) {
              return $q->where(['OrdersProducts.attributes->quantity' => 2]);
          })
          ->toArray();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $products = $this->OrdersProducts->find()->where([
            'attributes->order_id' => $order->id,
            'attributes->quantity' => 2,
            ])->toArray();

            $this->assertNotEmpty($products);
        }
    }

    public function testSaveAssociated()
    {
        $order = [
          'attributes' => ['date' => '2022-07-13'],
          'products' => [
            ['attributes' => ['name' => 'Superman'], '_joinData' => ['attributes' => ['quantity' => 1]]],
            ['attributes' => ['name' => 'Loïs Lane'], '_joinData' => ['attributes' => ['quantity' => 1]]],
          ],
        ];

        $order = $this->Orders->newEntity($order);
        $order = $this->Orders->saveOrFail($order);

        $this->assertNotEmpty($order->id);
        $this->assertEquals(2, count($order->products));
        foreach ($order->products as $product) {
            $this->assertNotEmpty($product->id);
            $this->assertEquals($order->id, $product->_joinData->{'attributes->order_id'});
            $this->assertEquals($product->id, $product->_joinData->{'attributes->product_id'});
            $this->assertEquals(1, $product->_joinData->{'attributes->quantity'});
        }
    }

    public function testCascadeDelete(): void
    {
        $id = $this->orders[0]['id'];
        $order = $this->Orders->get($id);

        $this->assertNotEquals(0, $this->OrdersProducts->find()->where(['attributes->order_id' => $id])->count());
        $this->Orders->deleteOrFail($order);
        $this->assertEquals(0, $this->OrdersProducts->find()->where(['attributes->order_id' => $id])->count());
    }
}
