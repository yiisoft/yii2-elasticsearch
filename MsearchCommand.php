<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace micetm\elasticsearch;

use yii\base\Component;
use yii\helpers\Json;

/**
 * The [[BulkCommand]] class implements the API for accessing the elasticsearch bulk REST API.
 *
 * Further details on bulk API is available in
 * [elasticsearch guide](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html).
 *
 * @author Konstantin Sirotkin <beowulfenator@gmail.com>
 * @since 2.0.5
 */
class MsearchCommand extends Component
{
    private const METHOD = '_msearch';

    /**
     * @var Connection
     */
    public $db;

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
     * @var string Default index to execute the queries on. Defaults to null meaning that index needs to be specified in every action.
     */
    public $index;

    /**
     * Executes the bulk command.
     * @return mixed
     * @throws yii\base\InvalidCallException
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-multi-search.html
     */
    public function execute($raw = false)
    {
        $endpoint = ['_all', self::METHOD];
        if ($this->index !== null) {
            $endpoint = [$this->index, self::METHOD];
        }

        if (empty($this->actions)) {
            $body = '{}' . "\n";
        } elseif (is_array($this->actions)) {
            $body = '';
            foreach ($this->actions as $action) {
                if (empty($action)) {
                    $body .= '{}' . "\n";
                    continue;
                }
                $body .= Json::encode($action) . "\n";
            }
        } else {
            $body = $this->actions . "\n";
        }

        $result = $this->db->get($endpoint, $this->options, $body);
        if ($result === false) {
            throw new Exception('Elasticsearch search query failed.');
        }
        if ($raw) {
            return $result;
        }

        if (!isset($result['responses']) || !is_array($result['responses']) || count($result['responses']) < 1) {
            return [];
        }

        $data = [];
        foreach ($result['responses'] as $i => $item) {
            $data[$i] = isset($item['hits']['hits']) ? $item['hits']['hits'] : [];
        }
        return $data;
    }

    /**
     * Adds an action to the command. Will overwrite existing actions if they are specified as a string.
     * @param array $action Action expressed as an array (will be encoded to JSON automatically).
     */
    public function addAction($body, $heades = [])
    {
        $this->actions[] = $heades;
        $this->actions[] = $body;
    }
}
