<?php

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\Connection;

/**
 * @group elasticsearch
 */
class CommandTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        parent::setUp();
        $this->connection = $this->getConnection();
    }

    public function testIndexStats()
    {
        $cmd = $this->connection->createCommand();
        if (!$cmd->indexExists('yii2test2')) {
            $cmd->createIndex('yii2test2');
        }
        $stats = $cmd->getIndexStats();
        $this->assertArrayHasKey('_all', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('indices', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('yii2test2', $stats['indices'], print_r(array_keys($stats['indices']), true));

        $stats = $cmd->getIndexStats('yii2test2');
        $this->assertArrayHasKey('_all', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('indices', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('yii2test2', $stats['indices'], print_r(array_keys($stats['indices']), true));
    }
}
