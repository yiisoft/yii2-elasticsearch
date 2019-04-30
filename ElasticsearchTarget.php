<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;

/**
 * ElasticsearchTarget stores log messages in a elasticsearch index.
 *
 * @author Eugene Terentev <eugene@terentev.net>
 * @since 2.0.5
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
    public $db = 'elasticsearch';
    /**
     * @var array $options URL options.
     */
    public $options = [];
    /**
     * @var boolean If true, context will be logged as a separate message after all other messages.
     */
    public $logContext = true;
    /**
     * @var boolean If true, context will be included in every message.
     * This is convenient if you log application errors and analyze them with tools like Kibana.
     */
    public $includeContext = false;
    /**
     * @var boolean If true, context message will cached once it's been created. Makes sense to use with [[includeContext]].
     */
    public $cacheContext = false;

    /**
     * @var string Context message cache (can be used multiple times if context is appended to every message)
     */
    protected $_contextMessage = null;


    /**
     * This method will initialize the [[elasticsearch]] property to make sure it refers to a valid Elasticsearch connection.
     * @throws InvalidConfigException if [[elasticsearch]] is invalid.
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        $messages = array_map([$this, 'prepareMessage'], $this->messages);
        $body = implode("\n", $messages) . "\n";
        $this->db->post([$this->index, $this->type, '_bulk'], $this->options, $body);
    }

    /**
     * If [[includeContext]] property is false, returns context message normally.
     * If [[includeContext]] is true, returns an empty string (so that context message in [[collect]] is not generated),
     * expecting that context will be appended to every message in [[prepareMessage]].
     * @return array the context information
     */
    protected function getContextMessage()
    {
        if (null === $this->_contextMessage || !$this->cacheContext) {
            $this->_contextMessage = ArrayHelper::filter($GLOBALS, $this->logVars);
        }

        return $this->_contextMessage;
    }

    /**
     * Processes the given log messages.
     * This method will filter the given messages with [[levels]] and [[categories]].
     * And if requested, it will also export the filtering result to specific medium (e.g. email).
     * Depending on the [[includeContext]] attribute, a context message will be either created or ignored.
     * @param array $messages log messages to be processed. See [[Logger::messages]] for the structure
     * of each message.
     * @param bool $final whether this method is called at the end of the current application
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge($this->messages, static::filterMessages($messages, $this->getLevels(), $this->categories, $this->except));
        $count = count($this->messages);
        if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {
            if (!$this->includeContext && $this->logContext) {
                $context = $this->getContextMessage();
                if (!empty($context)) {
                    $this->messages[] = [$context, Logger::LEVEL_INFO, 'application', YII_BEGIN_TIME];
                }
            }

            // set exportInterval to 0 to avoid triggering export again while exporting
            $oldExportInterval = $this->exportInterval;
            $this->exportInterval = 0;
            $this->export();
            $this->exportInterval = $oldExportInterval;

            $this->messages = [];
        }
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
            'level' => Logger::getLevelName($level),
            '@timestamp' => date('c', $timestamp),
        ];

        if (isset($message[4])) {
            $result['trace'] = $message[4];
        }

        //Exceptions get parsed into an array, text and arrays are passed as is, other types are var_dumped
        if ($text instanceof \Exception) {
            //convert exception to array for easier analysis
            $result['message'] = [
                'message' => $text->getMessage(),
                'file' => $text->getFile(),
                'line' => $text->getLine(),
                'trace' => $text->getTraceAsString(),
            ];
        } elseif (is_array($text) || is_string($text)) {
            $result['message'] = $text;
        } else {
            $result['message'] = VarDumper::export($text);
        }

        if ($this->includeContext) {
            $result['context'] = $this->getContextMessage();
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
