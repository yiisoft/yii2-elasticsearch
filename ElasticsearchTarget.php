<?php

namespace yii\elasticsearch;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\Json;
use yii\log\Logger;
use yii\log\Target;

/**
 * ElasticsearchTarget stores log messages in a elasticsearch index.
 *
 * @author Eugene Terentev <eugene@terentev.net>
 */
class ElasticsearchTarget extends Target
{
    /**
     * @var string Elasticsearch index name.
     */
    public $index = 'yii';
    /**
     * @var string Elasticsearch type name.
     */
    public $type = 'log';
    /**
     * @var Connection|array|string the elasticsearch connection object or the application component ID
     * of the elasticsearch connection.
     */
    public $elasticsearch = 'elasticsearch';
    /**
     * @var array $options URL options.
     */
    public $options = [];

    /**
     * This method will initialize the [[elasticsearch]] property to make sure it refers to a valid Elasticsearch connection.
     * @throws InvalidConfigException if [[elasticsearch]] is invalid.
     */
    public function init()
    {
        parent::init();
        $this->elasticsearch = Instance::ensure($this->elasticsearch, Connection::className());
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        $messages = array_map([$this, 'prepareMessage'], $this->messages);
        $body = implode("\n", $messages);
        $this->elasticsearch->post([$this->index, $this->type, '_bulk'], $this->options, $body);
    }

    /**
     * Prepares a log message.
     * @param array $message The log message to be formatted.
     * @return string
     */
    public function prepareMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;

        $result = [
            'category' => $category,
            'message' => $text,
            'level' => Logger::getLevelName($level),
            '@timestamp' => date('c', $timestamp),
        ];

        if (isset($message[4]) === true) {
            $result['trace'] = $message[4];
        }

        $message = implode("\n", [
            Json::encode([
                'index' => new \stdClass()
            ]),
            Json::encode($result)
        ]);

        return $message;
    }
}