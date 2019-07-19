<?php

namespace yiiunit\extensions\elasticsearch;

use Exception;
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

    /**
     * @throws Exception
     */
    public function testBulkUpsert()
    {
        $cmd = $this->connection->createCommand();
        $index = 'yii2-test-bulk-upsert';
        if (!$cmd->indexExists($index)) {
            $cmd->createIndex($index);
        }

        $cmd->insert($index, '_doc', [
            'field1' => 'value1.1',
            'field2' => 'value2.1',
            'intField' => 133,
            'id' => 1,
        ], 1);

        $cmd->insert($index, '_doc', [
            'field1' => 'value1.2',
            'field2' => 'value2.2',
            'intField' => 233,
            'id' => 2,
        ], 2);

        $data = [
            [
                'field1' => 'value1.1new',
                'field2' => 'value2.1new',
                'intField' => 1331,
                'id' => 1
            ],
            [
                'field1' => 'value1.3',
                'field2' => 'value2.3',
                'intField' => 333,
                'id' => 3
            ],
        ];

        $cmd->bulkUpsert($index, '_doc', $data, 'id');

        $data = $cmd->get($index, '_doc', 1);
        $this->assertNotEmpty($data['_source']);
        $data = $data['_source'];
        $this->assertEquals('value1.1new', $data['field1']);
        $this->assertEquals('value2.1new', $data['field2']);
        $this->assertEquals(1331, $data['intField']);

        $data = $cmd->get($index, '_doc', 2);
        $this->assertNotEmpty($data['_source']);
        $data = $data['_source'];
        $this->assertEquals('value1.2', $data['field1']);
        $this->assertEquals('value2.2', $data['field2']);
        $this->assertEquals(233, $data['intField']);

        $data = $cmd->get($index, '_doc', 3);
        $this->assertNotEmpty($data['_source']);
        $data = $data['_source'];
        $this->assertEquals('value1.3', $data['field1']);
        $this->assertEquals('value2.3', $data['field2']);
        $this->assertEquals(333, $data['intField']);

        $cmd->deleteIndex($index);
    }

    public function testCopyIndex()
    {
        $cmd = $this->connection->createCommand();
        $index1 = 'yii2-test-copy-index-1';
        $index2 = 'yii2-test-copy-index-2';

        $mapping = [
            'mappings' => [
                '_doc' => [
                    'properties' => [
                        'field1' => ['type' => 'keyword'],
                        'field2' => ['type' => 'keyword'],
                    ],
                ],
            ],
        ];

        if (!$cmd->indexExists($index1)) {
            $cmd->createIndex($index1, $mapping);
        }

        if (!$cmd->indexExists($index2)) {
            $cmd->createIndex($index2, $mapping);
        }

        $cmd->insert($index1, '_doc', [
            'field1' => 'value1.1',
            'field2' => 'value2.1',
        ], 1);

        $cmd->insert($index1, '_doc', [
            'field1' => 'value1.2',
            'field2' => 'value2.2',
        ], 2);

        $cmd->refreshIndex($index1);
        $cmd->copyIndex($index1, $index2);
        $cmd->refreshIndex($index2);

        $data = $cmd->get($index2, '_doc', 1);
        $this->assertNotEmpty($data['_source']);
        $data = $data['_source'];
        $this->assertEquals('value1.1', $data['field1']);
        $this->assertEquals('value2.1', $data['field2']);

        $data = $cmd->get($index2, '_doc', 2);
        $this->assertNotEmpty($data['_source']);
        $data = $data['_source'];
        $this->assertEquals('value1.2', $data['field1']);
        $this->assertEquals('value2.2', $data['field2']);

        $cmd->deleteIndex($index1);
        $cmd->deleteIndex($index2);
    }
}
