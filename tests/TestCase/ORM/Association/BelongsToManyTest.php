<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\ORM\Association;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Lqdt\OrmJson\Test\Fixture\DataGenerator;

class BelongsToManyTest extends TestCase
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
          ->generate(50);

        $this->Products->datFieldBelongsToMany('Orders', [
          'foreignKey' => 'attributes->product_id',
          'targetForeignKey' => 'attributes->order_id',
          'dependent' => true,
        ]);

        $this->Orders->datFieldBelongsToMany('Products', [
          'foreignKey' => 'attributes->order_id',
          'targetForeignKey' => 'attributes->product_id',
          'dependent' => true,
        ]);

        $this->Orders->saveManyOrFail($this->Orders->newEntities($this->orders));
        $this->OrdersProducts->saveManyOrFail($this->OrdersProducts->newEntities($this->details));
        $this->Products->saveManyOrFail($this->Products->newEntities($this->products));
    }

    public function tearDown(): void
    {
        unset($this->Orders);
        unset($this->OrdersProducts);
        unset($this->Products);
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
            }
        }
    }

    public function testOrderedContain(): void
    {
        $orders = $this->Orders
          ->find()
          ->contain('Products', function ($q) {
              return $q->order(['Products.attributes->price']);
          })
          ->toArray();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $prev = null;
            $this->assertNotEmpty($order->products);
            foreach ($order->products as $product) {
                if ($prev !== null) {
                    $this->assertTrue($product->{'attributes->price'} <= $prev);
                    $prev = $product->{'attributes->price'};
                }
            }
        }
    }

    public function testFilteredContain(): void
    {
        $orders = $this->Orders
          ->find()
          ->contain('Products', function ($q) {
              return $q->where(['Products.attributes->price >' => 10]);
          })
          ->toArray();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $this->assertNotEmpty($order->products);
            foreach ($order->products as $product) {
                $this->assertTrue($product->{'attributes->price'} > 10);
            }
        }
    }

    public function testMatching(): void
    {
        $name = $this->products[0]['attributes']['name'];

        $orders = $this->Orders
          ->find()
          ->matching('Products', function ($q) use ($name) {
              return $q->where(['Products.attributes->name' => $name]);
          })
          ->toArray();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $this->assertEquals($name, $order->_matchingData['Products']['attributes']['name']);
        }
    }

    public function testInnerJoinWith(): void
    {
        $name = $this->products[0]['attributes']['name'];

        $orders = $this->Orders
          ->find()
          ->innerJoinWith('Products', function ($q) use ($name) {
              return $q->where(['Products.attributes->name' => $name]);
          })
          ->toArray();

        $this->assertNotEmpty($orders);

        $product = $this->Orders->Products->find()->where(['attributes->name' => $name])->contain(['Orders'])->firstOrFail();

        $this->assertEquals(count($orders), count($product->orders));
    }

    public function testSaveAssociated()
    {
        $order = [
          'attributes' => ['date' => '2022-07-13'],
          'products' => [
            ['attributes' => ['name' => 'Superman']],
            ['attributes' => ['name' => 'Loïs Lane']],
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
        }

        // Append mode
        $this->Orders->Products->setSaveStrategy('append');
        $order->products = $this->Products->newEntities([['attributes' => ['name' => 'Lex Luthor']]]);
        $order->setDirty('products', true);
        $order = $this->Orders->saveOrFail($order);
        $order = $this->Orders->loadInto($order, ['Products']);

        $this->assertEquals(3, count($order->products));

        // Replace mode
        $this->Orders->Products->setSaveStrategy('replace');
        $order->products = $this->Products->newEntities([['attributes' => ['name' => 'Ultron hacked !']]]);
        $order->setDirty('products', true);
        $order = $this->Orders->saveOrFail($order);
        $order = $this->Orders->loadInto($order, ['Products']);

        $this->assertEquals(1, count($order->products));
    }

    public function testCascadeDelete(): void
    {
        $id = $this->orders[0]['id'];
        $order = $this->Orders->get($id);

        $this->assertNotEquals(0, $this->OrdersProducts->find()->where(['attributes->order_id' => $id])->count());
        $this->Orders->deleteOrFail($order);
        $this->assertEquals(0, $this->OrdersProducts->find()->where(['attributes->order_id' => $id])->count());
    }

    public function testLinkReplaceLinksAndUnlink(): void
    {
        $id = $this->orders[0]['id'];
        $order = $this->Orders->get($id, ['contain' => ['Products']]);

        $this->assertEquals(9, count($order->products));

        $this->Orders->Products->unlink($order, $order->products);
        $order = $this->Orders->get($id, ['contain' => ['Products']]);

        $this->assertEquals(0, count($order->products));

        $products = $this->Products->find()->toArray();
        $this->Orders->Products->link($order, $products);
        $order = $this->Orders->get($id, ['contain' => ['Products']]);

        $this->assertEquals(20, count($order->products));

        $products = $this->Products->find()->limit(5)->toArray();
        $this->Orders->Products->replaceLinks($order, $products);
        $order = $this->Orders->get($id, ['contain' => ['Products']]);

        $this->assertEquals(5, count($order->products));
    }
}
