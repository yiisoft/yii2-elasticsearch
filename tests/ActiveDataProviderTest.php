<?php

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\ActiveDataProvider;
use yii\elasticsearch\Connection;
use yii\elasticsearch\Query;
use yiiunit\extensions\elasticsearch\data\ar\ActiveRecord;
use yiiunit\extensions\elasticsearch\data\ar\Customer;

class ActiveDataProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /* @var $db Connection */
        $db = ActiveRecord::$db = $this->getConnection();

        // delete index
        if ($db->createCommand()->indexExists(Customer::index())) {
            $db->createCommand()->deleteIndex(Customer::index());
        }
        $db->createCommand()->createIndex(Customer::index());

        $command = $db->createCommand();
        Customer::setUpMapping($command);

        $db->createCommand()->refreshIndex(Customer::index());

        $customer = new Customer();
        $customer->_id = 1;
        $customer->setAttributes(['email' => 'user1@example.com', 'name' => 'user1', 'address' => 'address1', 'status' => 1], false);
        $customer->save(false);
        $customer = new Customer();
        $customer->_id = 2;
        $customer->setAttributes(['email' => 'user2@example.com', 'name' => 'user2', 'address' => 'address2', 'status' => 1], false);
        $customer->save(false);
        $customer = new Customer();
        $customer->_id = 3;
        $customer->setAttributes(['email' => 'user3@example.com', 'name' => 'user3', 'address' => 'address3', 'status' => 2], false);
        $customer->save(false);

        $db->createCommand()->refreshIndex(Customer::index());
    }

    // Tests :

    public function testQuery()
    {
        $query = new Query();
        $query->from(Customer::index(), 'customer');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
        ]);
        $models = $provider->getModels();
        $this->assertEquals(3, count($models));

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
            'pagination' => [
                'pageSize' => 1,
            ]
        ]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
    }

    public function testGetAggregations()
    {
        $provider = new ActiveDataProvider([
            'query' => Customer::find()->addAggregate('agg_status', [
                'terms' => [
                    'field' => 'status'
                ]
            ]),
        ]);
        $models = $provider->getModels();
        $this->assertEquals(3, count($models));

        $aggregations = $provider->getAggregations();
        $buckets = $aggregations['agg_status']['buckets'];
        $this->assertEquals(2, count($buckets));
        $status_1 = $buckets[array_search(1, array_column($buckets, 'key'))];
        $status_2 = $buckets[array_search(2, array_column($buckets, 'key'))];

        $this->assertEquals(2, $status_1['doc_count']);
        $this->assertEquals(1, $status_2['doc_count']);
    }

    public function testActiveQuery()
    {
        $provider = new ActiveDataProvider([
            'query' => Customer::find(),
        ]);
        $models = $provider->getModels();
        $this->assertEquals(3, count($models));
        $this->assertTrue($models[0] instanceof Customer);
        $this->assertTrue($models[1] instanceof Customer);

        $provider = new ActiveDataProvider([
            'query' => Customer::find(),
            'pagination' => [
                'pageSize' => 1,
            ]
        ]);
        $models = $provider->getModels();
        $this->assertEquals(1, count($models));
    }

    public function testNonexistentIndex()
    {
        $query = new Query();
        $query->from('nonexistent', 'nonexistent');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
        ]);

        // as of ES 2.0 querying a non-existent index returns a 404
        $this->expectException('\yii\elasticsearch\Exception');
        $models = $provider->getModels();
    }

    public function testRefresh()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Customer::find(),
        ]);
        $this->assertEquals(3, $dataProvider->getTotalCount());

        // Create new query and set to the same dataprovider
        $dataProvider->query = Customer::find()->where(['name' => 'user2']);
        $dataProvider->refresh();
        $this->assertEquals(1, $dataProvider->getTotalCount());
    }
}
