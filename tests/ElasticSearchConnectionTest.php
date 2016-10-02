<?php

namespace yiiunit\extensions\elasticsearch;

use PHPUnit_Framework_MockObject_MockObject;
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
        $config            = require "data/config.php";
        $connection->nodes = [
            ['http_address' => $config['elasticsearch']['nodes'][0]['http_address']],
        ];
        $this->assertNull($connection->activeNode);
        $connection->init();
        $connection->open();
        $this->assertNotNull($connection->activeNode);
        $this->assertArrayHasKey('name', reset($connection->nodes));
//        $this->assertArrayHasKey('hostname', reset($connection->nodes));
        $this->assertArrayHasKey('version', reset($connection->nodes));
        $this->assertArrayHasKey('http_address', reset($connection->nodes));
    }

    /**
     * @dataProvider getPossibleHttpAddresses
     */
    public function testPopulateNodesUsesCorrectProtocol($httpAddress, $expectedUrl)
    {
        /**
         * @var Connection|PHPUnit_Framework_MockObject_MockObject $connection
         */
        $connection = $this->getMockBuilder(Connection::className())
            ->disableOriginalConstructor()
            ->setMethods(['httpRequest'])
            ->getMock();

        $connection->autodetectCluster = true;
        $connection->nodes             = [
            ['http_address' => $httpAddress]
        ];
        $connection->init();

        $reflectedMethod = new \ReflectionMethod($connection, 'populateNodes');
        $reflectedMethod->setAccessible(true);

        $connection->expects($this->once())
            ->method("httpRequest")
            ->with('GET', $expectedUrl)
            ->willReturn(['nodes' => $connection->nodes]);

        $reflectedMethod->invoke($connection);
    }

    public function getPossibleHttpAddresses()
    {
        return [
            'Regular HTTP'                 => ['test', 'http://test/_nodes'],
            'HTTPS'                        => ['https://test', 'https://test/_nodes'],
            'HTTP with protocol specified' => ['http://test', 'http://test/_nodes'],
        ];
    }
}
