<?php

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\Query;

/**
 * @group elasticsearch
 */
class QueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $command = $this->getConnection()->createCommand();

        // delete index
        if ($command->indexExists('query-test')) {
            $command->deleteIndex('query-test');
        }
        $command->createIndex('query-test');

        $command->setMapping('query-test', 'user', [
            'properties' => [
                'name' => [ 'type' => 'keyword', 'store' => true ],
                'email' => [ 'type' => 'keyword', 'store' => true ],
                'status' => [ 'type' => 'integer', 'store' => true ],
            ],
        ]);

        $command->insert('query-test', 'user', ['name' => 'user1', 'email' => 'user1@example.com', 'status' => 1], 1);
        $command->insert('query-test', 'user', ['name' => 'user2', 'email' => 'user2@example.com', 'status' => 1], 2);
        $command->insert('query-test', 'user', ['name' => 'user3', 'email' => 'user3@example.com', 'status' => 2], 3);
        $command->insert('query-test', 'user', ['name' => 'user4', 'email' => 'user4@example.com', 'status' => 1], 4);
        $command->insert('query-test', 'user', ['name' => 'user5', 'email' => 'user5@example.com', 'status' => 1], 5);
        $command->insert('query-test', 'user', ['name' => 'user6', 'email' => 'user6@example.com', 'status' => 1], 6);
        $command->insert('query-test', 'user', ['name' => 'user7', 'email' => 'user7@example.com', 'status' => 2], 7);
        $command->insert('query-test', 'user', ['name' => 'user8', 'email' => 'user8@example.com', 'status' => 1], 8);
        $command->insert('query-test', 'user', ['name' => 'user9', 'email' => 'user9@example.com', 'status' => 1], 9);
        $command->insert('query-test', 'user', ['name' => 'usera', 'email' => 'user10@example.com', 'status' => 1], 10);
        $command->insert('query-test', 'user', ['name' => 'userb', 'email' => 'user11@example.com', 'status' => 2], 11);
        $command->insert('query-test', 'user', ['name' => 'userc', 'email' => 'user12@example.com', 'status' => 1], 12);

        $command->refreshIndex('query-test');
    }

    public function testFields()
    {
        $query = new Query;
        $query->from('query-test', 'user');

        $query->storedFields(['name', 'status']);
        $this->assertEquals(['name', 'status'], $query->storedFields);

        $query->storedFields('name', 'status');
        $this->assertEquals(['name', 'status'], $query->storedFields);

        $result = $query->one($this->getConnection());
        $this->assertEquals(2, count($result['fields']));
        $this->assertArrayHasKey('status', $result['fields']);
        $this->assertArrayHasKey('name', $result['fields']);
        $this->assertArrayHasKey('_id', $result);

        $query->storedFields([]);
        $this->assertEquals([], $query->storedFields);

        $result = $query->one($this->getConnection());
        $this->assertArrayNotHasKey('fields', $result);
        $this->assertArrayHasKey('_id', $result);

        $query->storedFields(null);
        $this->assertNull($query->storedFields);

        $result = $query->one($this->getConnection());
        $this->assertEquals(3, count($result['_source']));
        $this->assertArrayHasKey('status', $result['_source']);
        $this->assertArrayHasKey('email', $result['_source']);
        $this->assertArrayHasKey('name', $result['_source']);
        $this->assertArrayHasKey('_id', $result);
    }

    public function testOne()
    {
        $query = new Query;
        $query->from('query-test', 'user');

        $result = $query->one($this->getConnection());
        $this->assertEquals(3, count($result['_source']));
        $this->assertArrayHasKey('status', $result['_source']);
        $this->assertArrayHasKey('email', $result['_source']);
        $this->assertArrayHasKey('name', $result['_source']);
        $this->assertArrayHasKey('_id', $result);

        $result = $query->where(['name' => 'user1'])->one($this->getConnection());
        $this->assertEquals(3, count($result['_source']));
        $this->assertArrayHasKey('status', $result['_source']);
        $this->assertArrayHasKey('email', $result['_source']);
        $this->assertArrayHasKey('name', $result['_source']);
        $this->assertArrayHasKey('_id', $result);
        $this->assertEquals(1, $result['_id']);

        $result = $query->where(['name' => 'user15'])->one($this->getConnection());
        $this->assertFalse($result);
    }

    public function testAll()
    {
        $query = new Query;
        $query->from('query-test', 'user');

        $results = $query->limit(100)->all($this->getConnection());
        $this->assertEquals(12, count($results));
        $result = reset($results);
        $this->assertEquals(3, count($result['_source']));
        $this->assertArrayHasKey('status', $result['_source']);
        $this->assertArrayHasKey('email', $result['_source']);
        $this->assertArrayHasKey('name', $result['_source']);
        $this->assertArrayHasKey('_id', $result);

        $query = new Query;
        $query->from('query-test', 'user');

        $results = $query->where(['name' => 'user1'])->all($this->getConnection());
        $this->assertEquals(1, count($results));
        $result = reset($results);
        $this->assertEquals(3, count($result['_source']));
        $this->assertArrayHasKey('status', $result['_source']);
        $this->assertArrayHasKey('email', $result['_source']);
        $this->assertArrayHasKey('name', $result['_source']);
        $this->assertArrayHasKey('_id', $result);
        $this->assertEquals(1, $result['_id']);

        // indexBy
        $query = new Query;
        $query->from('query-test', 'user');

        $results = $query->limit(100)->indexBy('name')->all($this->getConnection());
        $this->assertEquals(12, count($results));
        ksort($results);
        $this->assertEquals([
            'user1',
            'user2',
            'user3',
            'user4',
            'user5',
            'user6',
            'user7',
            'user8',
            'user9',
            'usera',
            'userb',
            'userc'
        ], array_keys($results));
    }

    public function testScalar()
    {
        $query = new Query;
        $query->from('query-test', 'user');

        $result = $query->where(['name' => 'user1'])->scalar('name', $this->getConnection());
        $this->assertEquals('user1', $result);
        $result = $query->where(['name' => 'user1'])->scalar('noname', $this->getConnection());
        $this->assertNull($result);
        $result = $query->where(['name' => 'user15'])->scalar('name', $this->getConnection());
        $this->assertNull($result);
    }

    public function testColumn()
    {
        $query = new Query;
        $query->from('query-test', 'user');

        $result = $query->orderBy(['name' => SORT_ASC])->limit(4)->column('name', $this->getConnection());
        $this->assertEquals(['user1', 'user2', 'user3', 'user4'], $result);
        $result = $query->column('noname', $this->getConnection());
        $this->assertEquals([null, null, null, null], $result);
        $result = $query->where(['name' => 'user15'])->scalar('name', $this->getConnection());
        $this->assertNull($result);

    }

    public function testAndWhere() {
        $query = new Query;
        $query->where(1)
            ->andWhere(2)
            ->andWhere(3);

        $expected = [ 'and', 1, 2, 3 ];
        $this->assertEquals($expected, $query->where);
    }

    public function testOrWhere() {
        $query = new Query;
        $query->where(1)
            ->orWhere(2)
            ->orWhere(3);

        $expected = [ 'or', 1, 2, 3 ];
        $this->assertEquals($expected, $query->where);
    }

    public function testFilterWhere()
    {
        // should work with hash format
        $query = new Query;
        $query->filterWhere([
            '_id' => 0,
            'title' => '   ',
            'author_ids' => [],
        ]);
        $this->assertEquals(['_id' => 0], $query->where);

        $query->andFilterWhere(['status' => null]);
        $this->assertEquals(['_id' => 0], $query->where);

        $query->orFilterWhere(['name' => '']);
        $this->assertEquals(['_id' => 0], $query->where);

        // should work with operator format
        $query = new Query;
        $condition = ['like', 'name', 'Alex'];
        $query->filterWhere($condition);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['between', '_id', null, null]);
        $this->assertEquals($condition, $query->where);

        $query->orFilterWhere(['not between', '_id', null, null]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['in', '_id', []]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['not in', '_id', []]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['not in', '_id', []]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['like', '_id', '']);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['or like', '_id', '']);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['not like', '_id', '   ']);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['or not like', '_id', null]);
        $this->assertEquals($condition, $query->where);
    }

    public function testFilterWhereRecursively()
    {
        $query = new Query();
        $query->filterWhere([
            'and',
            ['like', 'name', ''],
            ['like', 'title', ''],
            ['_id' => 1],
            ['not', ['like', 'name', '']]
        ]);
        $this->assertEquals(['and', ['_id' => 1]], $query->where);
    }

    // TODO test facets

    // TODO test complex where() every edge of QueryBuilder

    public function testOrder()
    {
        $query = new Query;
        $query->orderBy('team');
        $this->assertEquals(['team' => SORT_ASC], $query->orderBy);

        $query->addOrderBy('company');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC], $query->orderBy);

        $query->addOrderBy('age');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_ASC], $query->orderBy);

        $query->addOrderBy(['age' => SORT_DESC]);
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_DESC], $query->orderBy);

        $query->addOrderBy('age ASC, company DESC');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_DESC, 'age' => SORT_ASC], $query->orderBy);
    }

    public function testLimitOffset()
    {
        $query = new Query;
        $query->limit(10)->offset(5);
        $this->assertEquals(10, $query->limit);
        $this->assertEquals(5, $query->offset);
    }


    /**
     * @since 2.0.4
     */
    public function testBatch()
    {
        $names = [
            'user1',
            'user2',
            'user3',
            'user4',
            'user5',
            'user6',
            'user7',
            'user8',
            'user9',
            'usera',
            'userb',
            'userc',
        ];

        $emails = [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
            'user4@example.com',
            'user5@example.com',
            'user6@example.com',
            'user7@example.com',
            'user8@example.com',
            'user9@example.com',
            'user10@example.com',
            'user11@example.com',
            'user12@example.com',
        ];

        //test each
        $query = new Query;
        $query->from('query-test', 'user')->limit(3)->orderBy(['name' => SORT_ASC])->indexBy('name')->options(['preference' => '_local']);
        //NOTE: preference -> _local has no influence on query result, everything's fine as long as query doesn't fail

        $result_keys = [];
        $result_values = [];
        foreach ($query->each('1m', $this->getConnection()) as $key => $value) {
            $result_keys[] = $key;
            $result_values[] = $value['_source']['email'];
        }

        $this->assertEquals(12, count($result_keys));
        $this->assertEquals($names, $result_keys);

        $this->assertEquals(12, count($result_values));
        $this->assertEquals($emails, $result_values);

        //test batch
        $query = new Query;
        $query->from('query-test', 'user')->limit(3)->orderBy(['name' => SORT_ASC])->indexBy('name')->options(['preference' => '_local']);
        //NOTE: preference -> _local has no influence on query result, everything's fine as long as query doesn't fail

        $results = [];
        foreach ($query->batch('1m', $this->getConnection()) as $batchId => $batch) {
            $results = $results + $batch;
        }

        $this->assertEquals(12, count($results));
        $this->assertEquals($names, array_keys($results));
        foreach ($names as $id => $name) {
            $this->assertEquals($emails[$id], $results[$name]['_source']['email']);
        }

        //test scan (no ordering)
        $query = new Query;
        $query->from('query-test', 'user')->limit(3);

        $results = [];
        foreach ($query->each('1m', $this->getConnection()) as $value) {
            $results[] = $value['_source']['name'];
        }

        $this->assertEquals(12, count($results));
        sort($results);
        $this->assertEquals($names, $results);
    }

    /**
     * @group postfilter
     * @since 2.0.5
     */
    public function testPostFilter()
    {
        $postFilter = [
            'term' => ['status' => 2]
        ];
        $query = new Query();
        $query->from('query-test', 'user');
        $query->postFilter($postFilter);
        $query->addAggregation('statuses', 'terms', ['field' => 'status']);
        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(3, $total);
    }

    /**
     * @group explain
     * @since 2.0.5
     */
    public function testExplain()
    {
        $query = new Query();
        $query->from('query-test', 'user');
        $query->explain(true);
        $result = $query->search($this->getConnection());
        $this->assertTrue(is_array($result['hits']['hits'][0]['_explanation']));
        $this->assertTrue(array_key_exists('_explanation', $result['hits']['hits'][0]));
    }

    /**
     * @group explain
     * @since 2.0.5
     */
    public function testNoExplain()
    {
        $query = new Query();
        $query->from('query-test', 'user');
        $result = $query->search($this->getConnection());
        $this->assertFalse(array_key_exists('_explanation', $result['hits']['hits'][0]));
    }

    public function testQueryWithWhere()
    {
        // make sure that both `query()` and `where()` work at the same time
        $query = new Query();
        $query->from('query-test', 'user');
        $query->where(['status' => 2]);
        $query->query(['term' => ['name' => 'userb']]);
        $result = $query->search($this->getConnection());

        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(1, $total);
    }

    public function testSuggest()
    {
        $cmd = $this->getConnection()->createCommand();
        $cmd->index = "query-test";

        $result = $cmd->suggest(['customer_name' => [
            'text' => 'user',
            'term' => [
                'field' => 'name'
            ]
        ]]);

        $this->assertCount(5, $result['customer_name'][0]['options']);
    }

    public function testRuntimeMappings()
    {
        // Check that Elasticsearch is version 7.11.0 or later before running this test
        $elasticsearchInfo = $this->getConnection()->get('/');
        if(!version_compare($elasticsearchInfo['version']['number'], '7.11.0', '>=')) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $query = new Query();
        $query->from('query-test', 'user');

        $query->runtimeMappings([
            'name_email' => [
                'type' => 'keyword',
                'script' => "emit(doc['name'].value + ':' + doc['email'].value)",
            ],
        ]);
        $this->assertEquals([
            'name_email' => [
                'type' => 'keyword',
                'script' => "emit(doc['name'].value + ':' + doc['email'].value)",
            ],
        ], $query->runtimeMappings);

        $query->fields(['name_email']);
        $this->assertEquals(['name_email'], $query->fields);

        $result = $query->search($this->getConnection());
        $this->assertArrayHasKey('name_email', $result['hits']['hits'][0]['fields']);
        $this->assertEquals($result['hits']['hits'][0]['fields']['name_email'][0], 'user1:user1@example.com');
    }
}
