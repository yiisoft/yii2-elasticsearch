<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use yii\base\Component;
use yii\base\InvalidCallException;
use yii\helpers\Json;

/**
 * The [[BulkCommand]] class implements the API for accessing the Elasticsearch bulk REST API.
 *
 * Further details on bulk API is available in
 * [Elasticsearch guide](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html).
 *
 * @author Konstantin Sirotkin <beowulfenator@gmail.com>
 * @since 2.0.5
 */
class BulkCommand extends Component
{
    /**
     * @var Connection
     */
    public $db;
    /**
     * @var string Default index to execute the queries on. Defaults to null meaning that index needs to be specified in every action.
     */
    public $index;
    /**
     * @var string Default type to execute the queries on. Defaults to null meaning that type needs to be specified in every action.
     */
    public $type;
    /**
     * @var array|string Actions to be executed in this bulk command, given as either an array of arrays or as one newline-delimited string.
     * All actions except delete span two lines.
     */
    public $actions;
    /**
     * @var array Options to be appended to the query URL.
     */
    public $options = [];


    /**
     * Executes the bulk command.
     * @return mixed
     * @throws \yii\base\InvalidCallException
     */
    public function execute()
    {
        //valid endpoints are /_bulk, /{index}/_bulk, and {index}/{type}/_bulk
        //for ES7+ type is omitted
        if ($this->index === null && $this->type === null) {
            $endpoint = ['_bulk'];
        } elseif ($this->index !== null && $this->type === null) {
            $endpoint = [$this->index, '_bulk'];
        } elseif ($this->index !== null && $this->type !== null) {
            if ($this->db->dslVersion >= 7) {
                $endpoint = [$this->index, '_bulk'];
            } else {
                $endpoint = [$this->index, $this->type, '_bulk'];
            }
        } else {
            throw new InvalidCallException('Invalid endpoint: if type is defined, index must be defined too.');
        }

        if (empty($this->actions)) {
            $body = '{}';
        } elseif (is_array($this->actions)) {
            $body = '';
            $prettyPrintSupported = property_exists('yii\\helpers\\Json', 'prettyPrint');
            if ($prettyPrintSupported) {
                $originalPrettyPrint = Json::$prettyPrint;
                Json::$prettyPrint = false; // ElasticSearch bulk API uses new lines as delimiters.
            }
            foreach ($this->actions as $action) {
                $body .= Json::encode($action) . "\n";
            }
            if ($prettyPrintSupported) {
                Json::$prettyPrint = $originalPrettyPrint;
            }
        } else {
            $body = $this->actions;
        }

        return $this->db->post($endpoint, $this->options, $body);
    }

    /**
     * Adds an action to the command. Will overwrite existing actions if they are specified as a string.
     * @param array $line1 First action expressed as an array (will be encoded to JSON automatically).
     * @param array|null $line2 Second action expressed as an array (will be encoded to JSON automatically).
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.x/docs-bulk.html
     */
    public function addAction($line1, $line2 = null)
    {
        if (!is_array($this->actions)) {
            $this->actions = [];
        }

        $this->actions[] = $line1;

        if ($line2 !== null) {
            $this->actions[] = $line2;
        }
    }

    /**
     * Adds a delete action to the command.
     * @param string $id Document ID
     * @param string|null $index Index that the document belongs to. Can be set to null if the command has
     * a default index ([[BulkCommand::$index]]) assigned.
     * @param string|null $type Type that the document belongs to. Can be set to null if the command has
     * a default type ([[BulkCommand::$type]]) assigned.
     */
    public function addDeleteAction($id, $index = null, $type = null)
    {
        $actionData = ['_id' => $id];

        if (!empty($index)) {
            $actionData['_index'] = $index;
        }

        if (!empty($type)) {
            $actionData['_type'] = $type;
        }

        $this->addAction(['delete' => $actionData]);
    }
}
