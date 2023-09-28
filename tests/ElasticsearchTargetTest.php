<?php
/**
 * @author Eugene Terentev <eugene@terentev.net>
 */

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\ElasticsearchTarget;
use yii\elasticsearch\Query;
use yii\log\Dispatcher;
use yii\log\Logger;

class ElasticsearchTargetTest extends TestCase
{
    public $logger;
    public $index = 'yiilogtest';
    public $type = 'log';

    public function testExport()
    {
        $logger = $this->logger;

        $logger->log('Test message', Logger::LEVEL_INFO, 'test-category');
        $logger->flush(true);
        $this->getConnection()->createCommand()->refreshIndex($this->index);

        $query = new Query();
        $query->from($this->index, $this->type);
        $message = $query->one($this->getConnection());
        $this->assertArrayHasKey('_source', $message);

        $source = $message['_source'];
        $this->assertArrayHasKey('@timestamp', $source);
        $this->assertArrayHasKey('message', $source);
        $this->assertArrayHasKey('level', $source);
        $this->assertArrayHasKey('category', $source);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $command = $this->getConnection()->createCommand();

        // delete index
        if ($command->indexExists($this->index)) {
            $command->deleteIndex($this->index);
        }

        $this->logger = new Logger();
        $dispatcher = new Dispatcher([
            'logger' => $this->logger,
            'targets' => [
                [
                    'class' => ElasticsearchTarget::className(),
                    'db' => $this->getConnection(),
                    'index' => $this->index,
                    'type' => $this->type,
                ]
            ]
        ]);
    }

    protected function tearDown(): void
    {
        $command = $this->getConnection()->createCommand();
        $command->deleteIndex($this->index);

        parent::tearDown();
    }


}
