<?php

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\Query;
use yii\elasticsearch\QueryBuilder;

/**
 * @group elasticsearch
 */
class QueryBuilderTest extends TestCase
{
    /**
     * @var string ES version
     */
    private $version;

    public function setUp()
    {
        parent::setUp();
        $command = $this->getConnection()->createCommand();

        // delete index
        if ($command->indexExists('yiitest')) {
            $command->deleteIndex('yiitest');
        }

        $info = $command->db->get('/');
        $this->version = $info['version']['number'];

        $this->prepareDbData();
    }

    private function prepareDbData()
    {
        $command = $this->getConnection()->createCommand();
        $command->insert('yiitest', 'article', ['title' => 'I love yii!', 'weight' => 1, 'created_at' => '2010-01-10'], 1);
        $command->insert('yiitest', 'article', ['title' => 'Symfony2 is another framework', 'weight' => 2, 'created_at' => '2010-01-15'], 2);
        $command->insert('yiitest', 'article', ['title' => 'Yii2 out now!', 'weight' => 3, 'created_at' => '2010-01-20'], 3);
        $command->insert('yiitest', 'article', ['title' => 'yii test', 'weight' => 4, 'created_at' => '2012-05-11'], 4);

        $command->flushIndex('yiitest');
    }

    public function testQueryBuilderRespectsQuery()
    {
        $queryParts = ['field' => ['title' => 'yii']];
        $queryBuilder = new QueryBuilder($this->getConnection());
        $query = new Query();
        $query->query = $queryParts;
        $build = $queryBuilder->build($query);
        $this->assertTrue(array_key_exists('queryParts', $build));
        $this->assertTrue(array_key_exists('query', $build['queryParts']));
        $this->assertSame($queryParts, $build['queryParts']['query']);
        $this->assertFalse(array_key_exists('match_all', $build['queryParts']), 'Match all should not be set');
    }

    /**
     * @group postfilter
     */
    public function testQueryBuilderPostFilterQuery()
    {
        $postFilter = [
            'bool' => [
                'must' => [
                    ['term' => ['title' => 'yii test']]
                ]
            ]
        ];
        $queryBuilder = new QueryBuilder($this->getConnection());
        $query = new Query();
        $query->postFilter($postFilter);
        $build = $queryBuilder->build($query);
        $this->assertSame($postFilter, $build['queryParts']['post_filter']);
    }

    public function testYiiCanBeFoundByQuery()
    {
        $queryParts = ['term' => ['title' => 'yii']];
        $query = new Query();
        $query->from('yiitest', 'article');
        $query->query = $queryParts;
        $result = $query->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);
    }

    public function testMinScore()
    {
        $queryParts = [
            'function_score' => [
                'boost_mode' => 'replace',
                'query' => ['term' => ['title' => 'yii']],
                'functions' => [
                    [
                        'script_score' => [
                            'script' => "doc['weight'].getValue()",
                        ]
                    ],
                ],
            ],
        ];
        //without min_score should get 2 documents with weights 1 and 4

        $query = new Query();
        $query->from('yiitest', 'article');
        $query->query($queryParts);

        $query->minScore(0.5);
        $result = $query->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);

        $query->minScore(2);
        $result = $query->search($this->getConnection());
        $this->assertEquals(1, $result['hits']['total']);

        $query->minScore(5);
        $result = $query->search($this->getConnection());
        $this->assertEquals(0, $result['hits']['total']);
    }

    public function testMltSearch()
    {
        $queryParts = [
            "more_like_this" => [
                "fields" => ["title"],
                "like_text" => "Mention YII now",
                "min_term_freq" => 1,
                "min_doc_freq" => 1,
            ]
        ];
        $query = new Query();
        $query->from('yiitest', 'article');
        $query->query = $queryParts;
        $result = $query->search($this->getConnection());
        $this->assertEquals(3, $result['hits']['total']);
    }

    public function testHalfBoundedRange()
    {
        // >= 2010-01-15, 3 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['>=', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $this->assertEquals(3, $result['hits']['total']);

        // >= 2010-01-15, 3 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['gte', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $this->assertEquals(3, $result['hits']['total']);

        // > 2010-01-15, 2 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['>', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);

        // > 2010-01-15, 2 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['gt', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);

        // <= 2010-01-20, 3 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['<=', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $this->assertEquals(3, $result['hits']['total']);

        // <= 2010-01-20, 3 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['lte', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $this->assertEquals(3, $result['hits']['total']);

        // < 2010-01-20, 2 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['<', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);

        // < 2010-01-20, 2 results
        $result = (new Query())
            ->from('yiitest', 'article')
            ->where(['lt', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);
    }

    public function testNotCondition()
    {
        $titles = [
            'Symfony2 is another framework',
            'yii test',
            'nonexistent',
        ];
        $result = (new Query)
            ->from('yiitest', 'article')
            ->where([ 'not', [ 'in', 'title.keyword', $titles ] ])
            ->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);
    }

    public function testInCondition()
    {
        $titles = [
            'Symfony2 is another framework',
            'yii test',
            'nonexistent',
        ];
        $result = (new Query)
            ->from('yiitest', 'article')
            ->where([ 'in', 'title.keyword', $titles ])
            ->search($this->getConnection());
        $this->assertEquals(2, $result['hits']['total']);
    }

    public function testBuildNotCondition()
    {
        $db = $this->getConnection();
        $qb = new QueryBuilder($db);

        $cond = [ 'title' => 'xyz' ];
        $operands = [ $cond ];

        $expected = [
            'bool' => [
                'must_not' => [
                    'bool' => [ 'must' => [ ['term'=>['title'=>'xyz']] ] ],
                ],
            ]
        ];
        $result = $this->invokeMethod($qb, 'buildNotCondition', ['not',$operands]);
        $this->assertEquals($expected, $result);
    }

    public function testBuildInCondition()
    {
        $db = $this->getConnection();
        $qb = new QueryBuilder($db);

        $expected = [
            'terms' => ['foo' => ['bar1', 'bar2']],
        ];
        $result = $this->invokeMethod($qb, 'buildInCondition', [
            'in',
            ['foo',['bar1','bar2']]
        ]);
        $this->assertEquals($expected, $result);
    }

    public function invokeMethod($obj, $methodName, $args)
    {
        $reflection = new \ReflectionObject($obj);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }
}
