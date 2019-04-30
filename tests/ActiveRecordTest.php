<?php

namespace yiiunit\extensions\elasticsearch;

use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\elasticsearch\Connection;
use yiiunit\framework\ar\ActiveRecordTestTrait;
use yiiunit\extensions\elasticsearch\data\ar\ActiveRecord;
use yiiunit\extensions\elasticsearch\data\ar\Customer;
use yiiunit\extensions\elasticsearch\data\ar\OrderItem;
use yiiunit\extensions\elasticsearch\data\ar\Order;
use yiiunit\extensions\elasticsearch\data\ar\Item;
use yiiunit\extensions\elasticsearch\data\ar\OrderWithNullFK;
use yiiunit\extensions\elasticsearch\data\ar\OrderItemWithNullFK;
use yiiunit\extensions\elasticsearch\data\ar\Animal;
use yiiunit\extensions\elasticsearch\data\ar\Dog;
use yiiunit\extensions\elasticsearch\data\ar\Cat;

/**
 * @group elasticsearch
 */
class ActiveRecordTest extends TestCase
{

    use ActiveRecordTestTrait;

    public function getCustomerClass()
    {
        return Customer::className();
    }

    public function getItemClass()
    {
        return Item::className();
    }

    public function getOrderClass()
    {
        return Order::className();
    }

    public function getOrderItemClass()
    {
        return OrderItem::className();
    }

    public function getOrderWithNullFKClass()
    {
        return OrderWithNullFK::className();
    }

    public function getOrderItemWithNullFKmClass()
    {
        return OrderItemWithNullFK::className();
    }

    /**
     * can be overridden to do things after save()
     */
    public function afterSave()
    {
        $this->getConnection()->createCommand()->flushIndex('yiitest');
    }

    public function setUp()
    {
        parent::setUp();

        /* @var $db Connection */
        $db = ActiveRecord::$db = $this->getConnection();

        // delete index
        if ($db->createCommand()->indexExists('yiitest')) {
            $db->createCommand()->deleteIndex('yiitest');
        }
        $db->createCommand()->createIndex('yiitest');

        $command = $db->createCommand();
        Customer::setUpMapping($command);
        Item::setUpMapping($command);
        Order::setUpMapping($command);
        OrderItem::setUpMapping($command);
        OrderWithNullFK::setUpMapping($command);
        OrderItemWithNullFK::setUpMapping($command);
        Animal::setUpMapping($command);

        $db->createCommand()->flushIndex('yiitest');

        $customer = new Customer();
        $customer->id = 1;
        $customer->setAttributes(['email' => 'user1@example.com', 'name' => 'user1', 'address' => 'address1', 'status' => 1], false);
        $customer->save(false);
        $customer = new Customer();
        $customer->id = 2;
        $customer->setAttributes(['email' => 'user2@example.com', 'name' => 'user2', 'address' => 'address2', 'status' => 1], false);
        $customer->save(false);
        $customer = new Customer();
        $customer->id = 3;
        $customer->setAttributes(['email' => 'user3@example.com', 'name' => 'user3', 'address' => 'address3', 'status' => 2], false);
        $customer->save(false);

//		INSERT INTO category (name) VALUES ('Books');
//		INSERT INTO category (name) VALUES ('Movies');

        $item = new Item();
        $item->id = 1;
        $item->setAttributes(['name' => 'Agile Web Application Development with Yii1.1 and PHP5', 'category_id' => 1], false);
        $item->save(false);
        $item = new Item();
        $item->id = 2;
        $item->setAttributes(['name' => 'Yii 1.1 Application Development Cookbook', 'category_id' => 1], false);
        $item->save(false);
        $item = new Item();
        $item->id = 3;
        $item->setAttributes(['name' => 'Ice Age', 'category_id' => 2], false);
        $item->save(false);
        $item = new Item();
        $item->id = 4;
        $item->setAttributes(['name' => 'Toy Story', 'category_id' => 2], false);
        $item->save(false);
        $item = new Item();
        $item->id = 5;
        $item->setAttributes(['name' => 'Cars', 'category_id' => 2], false);
        $item->save(false);

        $order = new Order();
        $order->id = 1;
        $order->setAttributes(['customer_id' => 1, 'created_at' => 1325282384, 'total' => 110.0, 'itemsArray' => [1, 2]], false);
        $order->save(false);
        $order = new Order();
        $order->id = 2;
        $order->setAttributes(['customer_id' => 2, 'created_at' => 1325334482, 'total' => 33.0, 'itemsArray' => [4, 5, 3]], false);
        $order->save(false);
        $order = new Order();
        $order->id = 3;
        $order->setAttributes(['customer_id' => 2, 'created_at' => 1325502201, 'total' => 40.0, 'itemsArray' => [2]], false);
        $order->save(false);

        $orderItem = new OrderItem();
        $orderItem->setAttributes(['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 30.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItem();
        $orderItem->setAttributes(['order_id' => 1, 'item_id' => 2, 'quantity' => 2, 'subtotal' => 40.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItem();
        $orderItem->setAttributes(['order_id' => 2, 'item_id' => 4, 'quantity' => 1, 'subtotal' => 10.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItem();
        $orderItem->setAttributes(['order_id' => 2, 'item_id' => 5, 'quantity' => 1, 'subtotal' => 15.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItem();
        $orderItem->setAttributes(['order_id' => 2, 'item_id' => 3, 'quantity' => 1, 'subtotal' => 8.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItem();
        $orderItem->setAttributes(['order_id' => 3, 'item_id' => 2, 'quantity' => 1, 'subtotal' => 40.0], false);
        $orderItem->save(false);

        $order = new OrderWithNullFK();
        $order->id = 1;
        $order->setAttributes(['customer_id' => 1, 'created_at' => 1325282384, 'total' => 110.0], false);
        $order->save(false);
        $order = new OrderWithNullFK();
        $order->id = 2;
        $order->setAttributes(['customer_id' => 2, 'created_at' => 1325334482, 'total' => 33.0], false);
        $order->save(false);
        $order = new OrderWithNullFK();
        $order->id = 3;
        $order->setAttributes(['customer_id' => 2, 'created_at' => 1325502201, 'total' => 40.0], false);
        $order->save(false);

        $orderItem = new OrderItemWithNullFK();
        $orderItem->setAttributes(['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 30.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItemWithNullFK();
        $orderItem->setAttributes(['order_id' => 1, 'item_id' => 2, 'quantity' => 2, 'subtotal' => 40.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItemWithNullFK();
        $orderItem->setAttributes(['order_id' => 2, 'item_id' => 4, 'quantity' => 1, 'subtotal' => 10.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItemWithNullFK();
        $orderItem->setAttributes(['order_id' => 2, 'item_id' => 5, 'quantity' => 1, 'subtotal' => 15.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItemWithNullFK();
        $orderItem->setAttributes(['order_id' => 2, 'item_id' => 3, 'quantity' => 1, 'subtotal' => 8.0], false);
        $orderItem->save(false);
        $orderItem = new OrderItemWithNullFK();
        $orderItem->setAttributes(['order_id' => 3, 'item_id' => 2, 'quantity' => 1, 'subtotal' => 40.0], false);
        $orderItem->save(false);

        (new Cat())->save(false);
        (new Dog())->save(false);
        
        $db->createCommand()->flushIndex('yiitest');
    }

    public function testSaveNoChanges()
    {
        // this should not fail with exception
        $customer = new Customer();
        // insert
        $customer->save(false);
        // update
        $customer->save(false);
    }

    public function testFindAsArray()
    {
        // asArray
        $customer = Customer::find()->where(['id' => 2])->asArray()->one();
        $this->assertEquals([
            'id' => 2,
            'email' => 'user2@example.com',
            'name' => 'user2',
            'address' => 'address2',
            'status' => 1,
//            '_score' => 1.0
                ], $customer['_source']);
    }

    public function testSearch()
    {
        $customers = Customer::find()->search()['hits'];
        $this->assertEquals(3, $customers['total']);
        $this->assertCount(3, $customers['hits']);
        $this->assertTrue($customers['hits'][0] instanceof Customer);
        $this->assertTrue($customers['hits'][1] instanceof Customer);
        $this->assertTrue($customers['hits'][2] instanceof Customer);

        // limit vs. totalcount
        $customers = Customer::find()->limit(2)->search()['hits'];
        $this->assertEquals(3, $customers['total']);
        $this->assertCount(2, $customers['hits']);

        // asArray
        $result = Customer::find()->asArray()->search()['hits'];
        $this->assertEquals(3, $result['total']);
        $customers = $result['hits'];
        $this->assertCount(3, $customers);
        $this->assertArrayHasKey('id', $customers[0]['_source']);
        $this->assertArrayHasKey('name', $customers[0]['_source']);
        $this->assertArrayHasKey('email', $customers[0]['_source']);
        $this->assertArrayHasKey('address', $customers[0]['_source']);
        $this->assertArrayHasKey('status', $customers[0]['_source']);
        $this->assertArrayHasKey('id', $customers[1]['_source']);
        $this->assertArrayHasKey('name', $customers[1]['_source']);
        $this->assertArrayHasKey('email', $customers[1]['_source']);
        $this->assertArrayHasKey('address', $customers[1]['_source']);
        $this->assertArrayHasKey('status', $customers[1]['_source']);
        $this->assertArrayHasKey('id', $customers[2]['_source']);
        $this->assertArrayHasKey('name', $customers[2]['_source']);
        $this->assertArrayHasKey('email', $customers[2]['_source']);
        $this->assertArrayHasKey('address', $customers[2]['_source']);
        $this->assertArrayHasKey('status', $customers[2]['_source']);

        // TODO test asArray() + fields() + indexBy()
        // find by attributes
        $result = Customer::find()->where(['name' => 'user2'])->search()['hits'];
        $customer = reset($result['hits']);
        $this->assertTrue($customer instanceof Customer);
        $this->assertEquals(2, $customer->id);

        // TODO test query() and filter()
    }

    // TODO test aggregations
//    public function testSearchFacets()
//    {
//        $result = Customer::find()->addAggregation('status_stats', ['field' => 'status'])->search();
//        $this->assertArrayHasKey('facets', $result);
//        $this->assertEquals(3, $result['facets']['status_stats']['count']);
//        $this->assertEquals(4, $result['facets']['status_stats']['total']); // sum of values
//        $this->assertEquals(1, $result['facets']['status_stats']['min']);
//        $this->assertEquals(2, $result['facets']['status_stats']['max']);
//    }

    public function testGetDb()
    {
        $this->mockApplication(['components' => ['elasticsearch' => Connection::className()]]);
        $this->assertInstanceOf(Connection::className(), ActiveRecord::getDb());
    }

    public function testGet()
    {
        $this->assertInstanceOf(Customer::className(), Customer::get(1));
        $this->assertNull(Customer::get(5));
    }

    public function testMget()
    {
        $this->assertEquals([], Customer::mget([]));

        $records = Customer::mget([1]);
        $this->assertCount(1, $records);
        $this->assertInstanceOf(Customer::className(), reset($records));

        $records = Customer::mget([5]);
        $this->assertCount(0, $records);

        $records = Customer::mget([1, 3, 5]);
        $this->assertCount(2, $records);
        $this->assertInstanceOf(Customer::className(), $records[0]);
        $this->assertInstanceOf(Customer::className(), $records[1]);
    }

    public function testFindLazy()
    {
        /* @var $customer Customer */
        $customer = Customer::findOne(2);
        $orders = $customer->orders;
        $this->assertCount(2, $orders);

        $orders = $customer->getOrders()->where(['between', 'created_at', 1325334000, 1325400000])->all();
        $this->assertCount(1, $orders);
        $this->assertEquals(2, $orders[0]->id);
    }

    public function testFindEagerViaRelation()
    {
        $orders = Order::find()->with('items')->orderBy('created_at')->all();
        $this->assertCount(3, $orders);
        $order = $orders[0];
        $this->assertEquals(1, $order->id);
        $this->assertTrue($order->isRelationPopulated('items'));
        $this->assertCount(2, $order->items);
        $this->assertEquals(1, $order->items[0]->id);
        $this->assertEquals(2, $order->items[1]->id);
    }

    public function testInsertNoPk()
    {
        $this->assertEquals(['id'], Customer::primaryKey());
        $pkName = 'id';

        $customer = new Customer;
        $customer->email = 'user4@example.com';
        $customer->name = 'user4';
        $customer->address = 'address4';

        $this->assertNull($customer->primaryKey);
        $this->assertNull($customer->oldPrimaryKey);
        $this->assertNull($customer->$pkName);
        $this->assertTrue($customer->isNewRecord);

        $customer->save();
        $this->afterSave();

        $this->assertNotNull($customer->primaryKey);
        $this->assertNotNull($customer->oldPrimaryKey);
        $this->assertNotNull($customer->$pkName);
        $this->assertEquals($customer->primaryKey, $customer->oldPrimaryKey);
        $this->assertEquals($customer->primaryKey, $customer->$pkName);
        $this->assertFalse($customer->isNewRecord);
    }

    public function testInsertPk()
    {
        $pkName = 'id';

        $customer = new Customer;
        $customer->$pkName = 5;
        $customer->email = 'user5@example.com';
        $customer->name = 'user5';
        $customer->address = 'address5';

        $this->assertTrue($customer->isNewRecord);

        $customer->save();

        $this->assertEquals(5, $customer->primaryKey);
        $this->assertEquals(5, $customer->oldPrimaryKey);
        $this->assertEquals(5, $customer->$pkName);
        $this->assertFalse($customer->isNewRecord);
    }

    public function testUpdatePk()
    {
        $pkName = 'id';

        $orderItem = Order::findOne([$pkName => 2]);
        $this->assertEquals(2, $orderItem->primaryKey);
        $this->assertEquals(2, $orderItem->oldPrimaryKey);
        $this->assertEquals(2, $orderItem->$pkName);

//		$this->setExpectedException('yii\base\InvalidCallException');
        $orderItem->$pkName = 13;
        $this->assertEquals(13, $orderItem->primaryKey);
        $this->assertEquals(2, $orderItem->oldPrimaryKey);
        $this->assertEquals(13, $orderItem->$pkName);
        $orderItem->save();
        $this->afterSave();
        $this->assertEquals(13, $orderItem->primaryKey);
        $this->assertEquals(13, $orderItem->oldPrimaryKey);
        $this->assertEquals(13, $orderItem->$pkName);

        $this->assertNull(Order::findOne([$pkName => 2]));
        $this->assertNotNull(Order::findOne([$pkName => 13]));
    }

    public function testFindLazyVia2()
    {
        /* @var $this TestCase|ActiveRecordTestTrait */
        /* @var $order Order */
        $orderClass = $this->getOrderClass();
        $pkName = 'id';

        $order = new $orderClass();
        $order->$pkName = 100;
        $this->assertEquals([], $order->items);
    }

    public function testScriptFields()
    {
        $orderItems = OrderItem::find()
            ->source('quantity', 'subtotal')
            ->scriptFields([
                'total' => [
                    'script' => [
                        'lang' => 'painless',
                        'inline' => "doc['quantity'].value * doc['subtotal'].value",
                    ]
                ]
            ])->all();
        $this->assertNotEmpty($orderItems);
        foreach ($orderItems as $item) {
            $this->assertEquals($item->subtotal * $item->quantity, $item->total);
        }
    }

    public function testFindAsArrayFields()
    {
        /* @var $this TestCase|ActiveRecordTestTrait */
        // indexBy + asArray
        $customers = Customer::find()->asArray()
            ->storedFields(['id', 'name'])->all();
        $this->assertEquals(3, count($customers));
        $this->assertArrayHasKey('id', $customers[0]['fields']);
        $this->assertArrayHasKey('name', $customers[0]['fields']);
        $this->assertArrayNotHasKey('email', $customers[0]['fields']);
        $this->assertArrayNotHasKey('address', $customers[0]['fields']);
        $this->assertArrayNotHasKey('status', $customers[0]['fields']);
        $this->assertArrayHasKey('id', $customers[1]['fields']);
        $this->assertArrayHasKey('name', $customers[1]['fields']);
        $this->assertArrayNotHasKey('email', $customers[1]['fields']);
        $this->assertArrayNotHasKey('address', $customers[1]['fields']);
        $this->assertArrayNotHasKey('status', $customers[1]['fields']);
        $this->assertArrayHasKey('id', $customers[2]['fields']);
        $this->assertArrayHasKey('name', $customers[2]['fields']);
        $this->assertArrayNotHasKey('email', $customers[2]['fields']);
        $this->assertArrayNotHasKey('address', $customers[2]['fields']);
        $this->assertArrayNotHasKey('status', $customers[2]['fields']);
    }

    public function testFindAsArraySourceFilter()
    {
        /* @var $this TestCase|ActiveRecordTestTrait */
        // indexBy + asArray
        $customers = Customer::find()->asArray()->source(['id', 'name'])->all();
        $this->assertCount(3, $customers);
        $this->assertArrayHasKey('id', $customers[0]['_source']);
        $this->assertArrayHasKey('name', $customers[0]['_source']);
        $this->assertArrayNotHasKey('email', $customers[0]['_source']);
        $this->assertArrayNotHasKey('address', $customers[0]['_source']);
        $this->assertArrayNotHasKey('status', $customers[0]['_source']);
        $this->assertArrayHasKey('id', $customers[1]['_source']);
        $this->assertArrayHasKey('name', $customers[1]['_source']);
        $this->assertArrayNotHasKey('email', $customers[1]['_source']);
        $this->assertArrayNotHasKey('address', $customers[1]['_source']);
        $this->assertArrayNotHasKey('status', $customers[1]['_source']);
        $this->assertArrayHasKey('id', $customers[2]['_source']);
        $this->assertArrayHasKey('name', $customers[2]['_source']);
        $this->assertArrayNotHasKey('email', $customers[2]['_source']);
        $this->assertArrayNotHasKey('address', $customers[2]['_source']);
        $this->assertArrayNotHasKey('status', $customers[2]['_source']);
    }

    public function testFindIndexBySource()
    {
        $customerClass = $this->getCustomerClass();
        /* @var $this TestCase|ActiveRecordTestTrait */
        // indexBy + asArray
        $customers = Customer::find()->indexBy('name')->source('id', 'name')->all();
        $this->assertCount(3, $customers);
        $this->assertTrue($customers['user1'] instanceof $customerClass);
        $this->assertTrue($customers['user2'] instanceof $customerClass);
        $this->assertTrue($customers['user3'] instanceof $customerClass);
        $this->assertNotNull($customers['user1']->id);
        $this->assertNotNull($customers['user1']->name);
        $this->assertNull($customers['user1']->email);
        $this->assertNull($customers['user1']->address);
        $this->assertNull($customers['user1']->status);
        $this->assertNotNull($customers['user2']->id);
        $this->assertNotNull($customers['user2']->name);
        $this->assertNull($customers['user2']->email);
        $this->assertNull($customers['user2']->address);
        $this->assertNull($customers['user2']->status);
        $this->assertNotNull($customers['user3']->id);
        $this->assertNotNull($customers['user3']->name);
        $this->assertNull($customers['user3']->email);
        $this->assertNull($customers['user3']->address);
        $this->assertNull($customers['user3']->status);

        // indexBy callable + asArray
        $customers = Customer::find()->indexBy(function ($customer) {
                    return $customer->id . '-' . $customer->name;
                })->storedFields('id', 'name')->all();
        $this->assertCount(3, $customers);
        $this->assertTrue($customers['1-user1'] instanceof $customerClass);
        $this->assertTrue($customers['2-user2'] instanceof $customerClass);
        $this->assertTrue($customers['3-user3'] instanceof $customerClass);
        $this->assertNotNull($customers['1-user1']->id);
        $this->assertNotNull($customers['1-user1']->name);
        $this->assertNull($customers['1-user1']->email);
        $this->assertNull($customers['1-user1']->address);
        $this->assertNull($customers['1-user1']->status);
        $this->assertNotNull($customers['2-user2']->id);
        $this->assertNotNull($customers['2-user2']->name);
        $this->assertNull($customers['2-user2']->email);
        $this->assertNull($customers['2-user2']->address);
        $this->assertNull($customers['2-user2']->status);
        $this->assertNotNull($customers['3-user3']->id);
        $this->assertNotNull($customers['3-user3']->name);
        $this->assertNull($customers['3-user3']->email);
        $this->assertNull($customers['3-user3']->address);
        $this->assertNull($customers['3-user3']->status);
    }

    public function testFindIndexByAsArrayFields()
    {
        /* @var $this TestCase|ActiveRecordTestTrait */
        // indexBy + asArray
        $customers = Customer::find()->indexBy('name')->asArray()->storedFields('id', 'name')->all();
        $this->assertCount(3, $customers);
        $this->assertArrayHasKey('id', $customers['user1']['fields']);
        $this->assertArrayHasKey('name', $customers['user1']['fields']);
        $this->assertArrayNotHasKey('email', $customers['user1']['fields']);
        $this->assertArrayNotHasKey('address', $customers['user1']['fields']);
        $this->assertArrayNotHasKey('status', $customers['user1']['fields']);
        $this->assertArrayHasKey('id', $customers['user2']['fields']);
        $this->assertArrayHasKey('name', $customers['user2']['fields']);
        $this->assertArrayNotHasKey('email', $customers['user2']['fields']);
        $this->assertArrayNotHasKey('address', $customers['user2']['fields']);
        $this->assertArrayNotHasKey('status', $customers['user2']['fields']);
        $this->assertArrayHasKey('id', $customers['user3']['fields']);
        $this->assertArrayHasKey('name', $customers['user3']['fields']);
        $this->assertArrayNotHasKey('email', $customers['user3']['fields']);
        $this->assertArrayNotHasKey('address', $customers['user3']['fields']);
        $this->assertArrayNotHasKey('status', $customers['user3']['fields']);

        // indexBy callable + asArray
        $customers = Customer::find()->indexBy(function ($customer) {
                    return reset($customer['fields']['id']) . '-' . reset($customer['fields']['name']);
                })->asArray()->storedFields('id', 'name')->all();
        $this->assertCount(3, $customers);
        $this->assertArrayHasKey('id', $customers['1-user1']['fields']);
        $this->assertArrayHasKey('name', $customers['1-user1']['fields']);
        $this->assertArrayNotHasKey('email', $customers['1-user1']['fields']);
        $this->assertArrayNotHasKey('address', $customers['1-user1']['fields']);
        $this->assertArrayNotHasKey('status', $customers['1-user1']['fields']);
        $this->assertArrayHasKey('id', $customers['2-user2']['fields']);
        $this->assertArrayHasKey('name', $customers['2-user2']['fields']);
        $this->assertArrayNotHasKey('email', $customers['2-user2']['fields']);
        $this->assertArrayNotHasKey('address', $customers['2-user2']['fields']);
        $this->assertArrayNotHasKey('status', $customers['2-user2']['fields']);
        $this->assertArrayHasKey('id', $customers['3-user3']['fields']);
        $this->assertArrayHasKey('name', $customers['3-user3']['fields']);
        $this->assertArrayNotHasKey('email', $customers['3-user3']['fields']);
        $this->assertArrayNotHasKey('address', $customers['3-user3']['fields']);
        $this->assertArrayNotHasKey('status', $customers['3-user3']['fields']);
    }

    public function testFindIndexByAsArray()
    {
        /* @var $customerClass \yii\db\ActiveRecordInterface */
        $customerClass = $this->getCustomerClass();

        /* @var $this TestCase|ActiveRecordTestTrait */
        // indexBy + asArray
        $customers = $customerClass::find()->asArray()->indexBy('name')->all();
        $this->assertCount(3, $customers);
        $this->assertArrayHasKey('id', $customers['user1']['_source']);
        $this->assertArrayHasKey('name', $customers['user1']['_source']);
        $this->assertArrayHasKey('email', $customers['user1']['_source']);
        $this->assertArrayHasKey('address', $customers['user1']['_source']);
        $this->assertArrayHasKey('status', $customers['user1']['_source']);
        $this->assertArrayHasKey('id', $customers['user2']['_source']);
        $this->assertArrayHasKey('name', $customers['user2']['_source']);
        $this->assertArrayHasKey('email', $customers['user2']['_source']);
        $this->assertArrayHasKey('address', $customers['user2']['_source']);
        $this->assertArrayHasKey('status', $customers['user2']['_source']);
        $this->assertArrayHasKey('id', $customers['user3']['_source']);
        $this->assertArrayHasKey('name', $customers['user3']['_source']);
        $this->assertArrayHasKey('email', $customers['user3']['_source']);
        $this->assertArrayHasKey('address', $customers['user3']['_source']);
        $this->assertArrayHasKey('status', $customers['user3']['_source']);

        // indexBy callable + asArray
        $customers = $customerClass::find()->indexBy(function ($customer) {
                    return $customer['_source']['id'] . '-' . $customer['_source']['name'];
                })->asArray()->all();
        $this->assertCount(3, $customers);
        $this->assertArrayHasKey('id', $customers['1-user1']['_source']);
        $this->assertArrayHasKey('name', $customers['1-user1']['_source']);
        $this->assertArrayHasKey('email', $customers['1-user1']['_source']);
        $this->assertArrayHasKey('address', $customers['1-user1']['_source']);
        $this->assertArrayHasKey('status', $customers['1-user1']['_source']);
        $this->assertArrayHasKey('id', $customers['2-user2']['_source']);
        $this->assertArrayHasKey('name', $customers['2-user2']['_source']);
        $this->assertArrayHasKey('email', $customers['2-user2']['_source']);
        $this->assertArrayHasKey('address', $customers['2-user2']['_source']);
        $this->assertArrayHasKey('status', $customers['2-user2']['_source']);
        $this->assertArrayHasKey('id', $customers['3-user3']['_source']);
        $this->assertArrayHasKey('name', $customers['3-user3']['_source']);
        $this->assertArrayHasKey('email', $customers['3-user3']['_source']);
        $this->assertArrayHasKey('address', $customers['3-user3']['_source']);
        $this->assertArrayHasKey('status', $customers['3-user3']['_source']);
    }

    public function testAfterFindGet()
    {
        /* @var $customerClass BaseActiveRecord */
        $customerClass = $this->getCustomerClass();

        $afterFindCalls = [];
        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_FIND, function ($event) use (&$afterFindCalls) {
            /* @var $ar BaseActiveRecord */
            $ar = $event->sender;
            $afterFindCalls[] = [get_class($ar), $ar->getIsNewRecord(), $ar->getPrimaryKey(), $ar->isRelationPopulated('orders')];
        });

        $customer = Customer::get(1);
        $this->assertNotNull($customer);
        $this->assertEquals([[$customerClass, false, 1, false]], $afterFindCalls);
        $afterFindCalls = [];

        $customer = Customer::mget([1, 2]);
        $this->assertNotNull($customer);
        $this->assertEquals([
            [$customerClass, false, 1, false],
            [$customerClass, false, 2, false],
                ], $afterFindCalls);
        $afterFindCalls = [];

        Event::off(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_FIND);
    }

    public function testFindEmptyPkCondition()
    {
        /* @var $this TestCase|ActiveRecordTestTrait */
        /* @var $orderItemClass \yii\db\ActiveRecordInterface */
        $orderItemClass = $this->getOrderItemClass();
        $orderItem = new $orderItemClass();
        $orderItem->setAttributes(['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 30.0], false);
        $orderItem->save(false);
        $this->afterSave();

        $orderItems = $orderItemClass::find()->where(['_id' => [$orderItem->getPrimaryKey()]])->all();
        $this->assertCount(1, $orderItems);

        $orderItems = $orderItemClass::find()->where(['_id' => []])->all();
        $this->assertCount(0, $orderItems);

        $orderItems = $orderItemClass::find()->where(['_id' => null])->all();
        $this->assertCount(0, $orderItems);

        $orderItems = $orderItemClass::find()->where(['IN', '_id', [$orderItem->getPrimaryKey()]])->all();
        $this->assertCount(1, $orderItems);

        $orderItems = $orderItemClass::find()->where(['IN', '_id', []])->all();
        $this->assertCount(0, $orderItems);

        $orderItems = $orderItemClass::find()->where(['IN', '_id', [null]])->all();
        $this->assertCount(0, $orderItems);
    }

    public function testArrayAttributes()
    {
        $this->assertTrue(is_array(Order::findOne(1)->itemsArray));
        $this->assertTrue(is_array(Order::findOne(2)->itemsArray));
        $this->assertTrue(is_array(Order::findOne(3)->itemsArray));
    }

    public function testArrayAttributeRelationLazy()
    {
        $order = Order::findOne(1);
        $items = $order->itemsByArrayValue;
        $this->assertCount(2, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));
        $this->assertTrue($items[1] instanceof Item);
        $this->assertTrue($items[2] instanceof Item);

        $order = Order::findOne(2);
        $items = $order->itemsByArrayValue;
        $this->assertCount(3, $items);
        $this->assertTrue(isset($items[3]));
        $this->assertTrue(isset($items[4]));
        $this->assertTrue(isset($items[5]));
        $this->assertTrue($items[3] instanceof Item);
        $this->assertTrue($items[4] instanceof Item);
        $this->assertTrue($items[5] instanceof Item);
    }

    public function testArrayAttributeRelationEager()
    {
        /* @var $order Order */
        $order = Order::find()->with('itemsByArrayValue')->where(['id' => 1])->one();
        $this->assertTrue($order->isRelationPopulated('itemsByArrayValue'));
        $items = $order->itemsByArrayValue;
        $this->assertCount(2, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));
        $this->assertTrue($items[1] instanceof Item);
        $this->assertTrue($items[2] instanceof Item);

        /* @var $order Order */
        $order = Order::find()->with('itemsByArrayValue')->where(['id' => 2])->one();
        $this->assertTrue($order->isRelationPopulated('itemsByArrayValue'));
        $items = $order->itemsByArrayValue;
        $this->assertCount(3, $items);
        $this->assertTrue(isset($items[3]));
        $this->assertTrue(isset($items[4]));
        $this->assertTrue(isset($items[5]));
        $this->assertTrue($items[3] instanceof Item);
        $this->assertTrue($items[4] instanceof Item);
        $this->assertTrue($items[5] instanceof Item);
    }

    public function testArrayAttributeRelationLink()
    {
        /* @var $order Order */
        $order = Order::find()->where(['id' => 1])->one();
        $items = $order->itemsByArrayValue;
        $this->assertCount(2, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));

        $item = Item::get(5);
        $order->link('itemsByArrayValue', $item);
        $this->afterSave();

        $items = $order->itemsByArrayValue;
        $this->assertCount(3, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));
        $this->assertTrue(isset($items[5]));

        // check also after refresh
        $this->assertTrue($order->refresh());
        $items = $order->itemsByArrayValue;
        $this->assertCount(3, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));
        $this->assertTrue(isset($items[5]));
    }

    public function testArrayAttributeRelationUnLink()
    {
        /* @var $order Order */
        $order = Order::find()->where(['id' => 1])->one();
        $items = $order->itemsByArrayValue;
        $this->assertCount(2, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));

        $item = Item::get(2);
        $order->unlink('itemsByArrayValue', $item);
        $this->afterSave();

        $items = $order->itemsByArrayValue;
        $this->assertCount(1, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertFalse(isset($items[2]));

        // check also after refresh
        $this->assertTrue($order->refresh());
        $items = $order->itemsByArrayValue;
        $this->assertCount(1, $items);
        $this->assertTrue(isset($items[1]));
        $this->assertFalse(isset($items[2]));
    }

    /**
     * https://github.com/yiisoft/yii2/issues/6065
     */
    public function testArrayAttributeRelationUnLinkBrokenArray()
    {
        /* @var $order Order */
        $order = Order::find()->where(['id' => 1])->one();

        $itemIds = $order->itemsArray;
        $removeId = reset($itemIds);
        $item = Item::get($removeId);
        $order->unlink('itemsByArrayValue', $item);
        $this->afterSave();

        $items = $order->itemsByArrayValue;
        $this->assertEquals(1, count($items));
        $this->assertFalse(isset($items[$removeId]));

        // check also after refresh
        $this->assertTrue($order->refresh());
        $items = $order->itemsByArrayValue;
        $this->assertEquals(1, count($items));
        $this->assertFalse(isset($items[$removeId]));
    }

    /**
     * @expectedException \yii\base\NotSupportedException
     */
    public function testArrayAttributeRelationUnLinkAll()
    {
        /* @var $order Order */
        $order = Order::find()->where(['id' => 1])->one();
        $items = $order->itemsByArrayValue;
        $this->assertEquals(2, count($items));
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));

        $order->unlinkAll('itemsByArrayValue');
        $this->afterSave();

        $items = $order->itemsByArrayValue;
        $this->assertEquals(0, count($items));

        // check also after refresh
        $this->assertTrue($order->refresh());
        $items = $order->itemsByArrayValue;
        $this->assertEquals(0, count($items));
    }

    public function testUnlinkAll()
    {
        // not supported by elasticsearch
    }

    /**
     * @expectedException \yii\base\NotSupportedException
     */
    public function testUnlinkAllAndConditionSetNull()
    {
        /* @var $customerClass \yii\db\BaseActiveRecord */
        $customerClass = $this->getCustomerClass();
        /* @var $orderClass \yii\db\BaseActiveRecord */
        $orderClass = $this->getOrderWithNullFKClass();

        // in this test all orders are owned by customer 1
        $orderClass::updateAll(['customer_id' => 1]);
        $this->afterSave();

        $customer = $customerClass::findOne(1);
        $this->assertEquals(3, count($customer->ordersWithNullFK));
        $this->assertEquals(1, count($customer->expensiveOrdersWithNullFK));
        $this->assertEquals(3, $orderClass::find()->count());
        $customer->unlinkAll('expensiveOrdersWithNullFK');
    }

    /**
     * @expectedException \yii\base\NotSupportedException
     */
    public function testUnlinkAllAndConditionDelete()
    {
        /* @var $customerClass \yii\db\BaseActiveRecord */
        $customerClass = $this->getCustomerClass();
        /* @var $orderClass \yii\db\BaseActiveRecord */
        $orderClass = $this->getOrderWithNullFKClass();

        // in this test all orders are owned by customer 1
        $orderClass::updateAll(['customer_id' => 1]);
        $this->afterSave();

        $customer = $customerClass::findOne(1);
        $this->assertEquals(3, count($customer->ordersWithNullFK));
        $this->assertEquals(1, count($customer->expensiveOrdersWithNullFK));
        $this->assertEquals(3, $orderClass::find()->count());
        $customer->unlinkAll('expensiveOrdersWithNullFK', true);
    }

    public function testPopulateRecordCallWhenQueryingOnParentClass()
    {
        $animal = Animal::find()->where(['type' => Dog::className()])->one();
        $this->assertEquals('bark', $animal->getDoes());

        $animal = Animal::find()->where(['type' => Cat::className()])->one();
        $this->assertEquals('meow', $animal->getDoes());
    }

    public function testAttributeAccess()
    {
        /* @var $customerClass \yii\db\ActiveRecordInterface */
        $customerClass = $this->getCustomerClass();
        $model = new $customerClass();

        $this->assertTrue($model->canSetProperty('name'));
        $this->assertTrue($model->canGetProperty('name'));
        $this->assertFalse($model->canSetProperty('unExistingColumn'));
        $this->assertFalse(isset($model->name));

        $model->name = 'foo';
        $this->assertTrue(isset($model->name));
        unset($model->name);
        $this->assertNull($model->name);

        // @see https://github.com/yiisoft/yii2-gii/issues/190
        $baseModel = new $customerClass();
        $this->assertFalse($baseModel->hasProperty('unExistingColumn'));


        /* @var $customer ActiveRecord */
        $customer = new $customerClass();
        $this->assertTrue($customer instanceof $customerClass);

        $this->assertTrue($customer->canGetProperty('id'));
        $this->assertTrue($customer->canSetProperty('id'));

        // tests that we really can get and set this property
        $this->assertNull($customer->id);
        $customer->id = 10;
        $this->assertNotNull($customer->id);

        $this->assertFalse($customer->canGetProperty('non_existing_property'));
        $this->assertFalse($customer->canSetProperty('non_existing_property'));
    }

    public function testBooleanAttribute()
    {
    }

    // TODO test AR with not mapped PK


    public function illegalValuesForFindByCondition()
    {
        return [
            [['id' => ['`id`=`id` and 1' => 1]], ['id' => 1]],
            [['id' => [
                'legal' => 1,
                '`id`=`id` and 1' => 1,
            ]], ['id' => 1]],
            [['id' => [
                'nested_illegal' => [
                    'false or 1=' => 1
                ]
            ]], null],

            [['id' => [
                'or',
                '1=1',
                'id' => 'id',
            ]], null],
            [['id' => [
                'or',
                '1=1',
                'id' => '1',
            ]], ['id' => 1]],
            [['id' => [
                'name' => 'Cars',
                'email' => 'test@example.com',
            ]], ['id' => 1]],
        ];
    }

    /**
     * @dataProvider illegalValuesForFindByCondition
     */
    public function testValueEscapingInFindByCondition($filterWithInjection, $expectedResult)
    {
        /* @var $itemClass \yii\db\ActiveRecordInterface */
        $itemClass = $this->getItemClass();

        $result = $itemClass::findOne($filterWithInjection['id']);
        if ($expectedResult === null) {
            $this->assertNull($result);
        } else {
            $this->assertNotNull($result);
            foreach($expectedResult as $col => $value) {
                $this->assertEquals($value, $result->$col);
            }
        }
    }
}
