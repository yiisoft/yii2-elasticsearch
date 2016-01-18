<?php

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\Query;
use yii\elasticsearch\QueryBuilder;

/**
 * @group elasticsearch
 */
class QueryBuilderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $command = $this->getConnection()->createCommand();

        // delete index
        if ($command->indexExists('yiitest')) {
            $command->deleteIndex('yiitest');
        }

        $this->prepareDbData();
    }

    private function prepareDbData()
    {
        $command = $this->getConnection()->createCommand();
        $command->insert('yiitest', 'article', ['title' => 'I love yii!'], 1);
        $command->insert('yiitest', 'article', ['title' => 'Symfony2 is another framework'], 2);
        $command->insert('yiitest', 'article', ['title' => 'Yii2 out now!'], 3);
        $command->insert('yiitest', 'article', ['title' => 'yii test'], 4);

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
        $queryParts = ['term' => ['title' => 'yii']];
        $query = new Query();
        $query->from('yiitest', 'article');
        $query->query = $queryParts;
        $query->minScore(0.9);
        $result = $query->search($this->getConnection());
        $this->assertEquals(0, $result['hits']['total']);

        $query->minScore(0.3);
        $result = $query->search($this->getConnection());
        $this->assertEquals(1, $result['hits']['total']);
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
