<?php

namespace yiiunit\extensions\elasticsearch;

use yii\elasticsearch\Connection;

/**
 * @group elasticsearch
 */
class ElasticSearchConnectionTest extends TestCase
{
    public function testOpen()
    {
        $connection = new Connection();
        $connection->autodetectCluster;
        $config = require "data/config.php";
        $connection->nodes = [
            ['http_address' => $config['elasticsearch']['nodes'][0]['http_address']],
        ];
        $this->assertNull($connection->activeNode);
        $connection->open();
        $this->assertNotNull($connection->activeNode);
        $this->assertArrayHasKey('name', reset($connection->nodes));
//        $this->assertArrayHasKey('hostname', reset($connection->nodes));
        $this->assertArrayHasKey('version', reset($connection->nodes));
        $this->assertArrayHasKey('http_address', reset($connection->nodes));
    }
}
