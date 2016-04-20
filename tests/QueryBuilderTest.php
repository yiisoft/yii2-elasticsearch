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
        $command->insert('yiitest', 'article', ['title' => 'I love yii!', 'weight' => 1], 1);
        $command->insert('yiitest', 'article', ['title' => 'Symfony2 is another framework', 'weight' => 2], 2);
        $command->insert('yiitest', 'article', ['title' => 'Yii2 out now!', 'weight' => 3], 3);
        $command->insert('yiitest', 'article', ['title' => 'yii test', 'weight' => 4], 4);

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
        $this->assertFalse(array_key_exists('match_all', $build['queryParts']), 'Match all should not be set');
        $this->assertSame($queryParts, $build['queryParts']['query']);
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
                    ['script_score' => [
                        'script' => "doc['weight'].getValue()",
                    ]],
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
}
