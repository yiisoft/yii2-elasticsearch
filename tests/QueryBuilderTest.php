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

    protected function setUp(): void
    {
        parent::setUp();
        $command = $this->getConnection()->createCommand();

        // delete index
        if ($command->indexExists('builder-test')) {
            $command->deleteIndex('builder-test');
        }

        $info = $command->db->get('/');
        $this->version = $info['version']['number'];

        $this->prepareDbData();
    }

    private function prepareDbData()
    {
        $command = $this->getConnection()->createCommand();
        $command->setMapping('builder-test', 'article', [
            'properties' => [
                'title' => ["type" => "keyword"],
                'created_at' => ["type" => "keyword"],
                'weight' => ["type" => "integer"],
            ]
        ]);
        $command->insert('builder-test', 'article', ['title' => 'I love yii!', 'weight' => 1, 'created_at' => '2010-01-10'], 1);
        $command->insert('builder-test', 'article', ['title' => 'Symfony2 is another framework', 'weight' => 2, 'created_at' => '2010-01-15'], 2);
        $command->insert('builder-test', 'article', ['title' => 'Yii2 out now!', 'weight' => 3, 'created_at' => '2010-01-20'], 3);
        $command->insert('builder-test', 'article', ['title' => 'yii test', 'weight' => 4, 'created_at' => '2012-05-11'], 4);

        $command->refreshIndex('builder-test');
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
        $query->from('builder-test', 'article');
        $query->query = $queryParts;
        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);
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
        $query->from('builder-test', 'article');
        $query->query($queryParts);

        $query->minScore(0.5);
        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);

        $query->minScore(2);
        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(1, $total);

        $query->minScore(5);
        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(0, $total);
    }

    public function testMltSearch()
    {
        $queryParts = [
            "more_like_this" => [
                "fields" => ["title"],
                "like" => "Mention YII now",
                "min_term_freq" => 1,
                "min_doc_freq" => 1,
            ]
        ];
        $query = new Query();
        $query->from('builder-test', 'article');
        $query->query = $queryParts;
        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(3, $total);
    }

    public function testHalfBoundedRange()
    {
        // >= 2010-01-15, 3 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['>=', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(3, $total);

        // >= 2010-01-15, 3 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['gte', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(3, $total);

        // > 2010-01-15, 2 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['>', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);

        // > 2010-01-15, 2 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['gt', 'created_at', '2010-01-15'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);

        // <= 2010-01-20, 3 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['<=', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(3, $total);

        // <= 2010-01-20, 3 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['lte', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(3, $total);

        // < 2010-01-20, 2 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['<', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);

        // < 2010-01-20, 2 results
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['lt', 'created_at', '2010-01-20'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);
    }

    public function testNotCondition()
    {
        $titles = [
            'yii',
            'test'
        ];

        $query = (new Query)
            ->from('builder-test', 'article')
            ->where(['not in', 'title', $titles]);

        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);
    }

    public function testInCondition()
    {
        $titles = [
            'yii',
            'out',
            'nonexistent',
        ];

        $query =  (new Query)
            ->from('builder-test', 'article')
            ->where(['in', 'title', $titles]);

        $result = $query->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(3, $total);
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

    public function testBuildMatchCondition()
    {
        $result = (new Query())
            ->from('builder-test', 'article')
            ->where(['match', 'title', 'yii'])
            ->search($this->getConnection());
        $total = is_array($result['hits']['total']) ? $result['hits']['total']['value'] : $result['hits']['total'];
        $this->assertEquals(2, $total);
    }
}
