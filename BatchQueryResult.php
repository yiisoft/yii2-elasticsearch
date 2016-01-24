<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use yii\db\BatchQueryResult as BaseBatchQueryResult;

/**
 * BatchQueryResult represents a batch query from which you can retrieve data in batches.
 *
 * You usually do not instantiate BatchQueryResult directly. Instead, you obtain it by
 * calling [[Query::batch()]] or [[Query::each()]]. Because BatchQueryResult implements the [[\Iterator]] interface,
 * you can iterate it to obtain a batch of data in each iteration.
 *
 * Batch size is determined by the [[Query::$limit]] setting. [[Query::$offset]] setting is ignored.
 * New batches will be obtained until the server runs out of results.
 *
 * If [[Query::$orderBy]] parameter is not set, batches will be processed using the highly efficient "scan" mode.
 * In this case, [[Query::$limit]] setting determines batch size per shard.
 * See [elasticsearch guide](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html)
 * for more information.
 *
 * Example:
 * ```php
 * $query = (new Query)->from('user');
 * foreach ($query->batch() as $i => $users) {
 *     // $users represents the rows in the $i-th batch
 * }
 * foreach ($query->each() as $user) {
 * }
 * ```
 *
 * @author Konstantin Sirotkin <beowulfenator@gmail.com>
 * @since 2.0.4
 */
class BatchQueryResult extends BaseBatchQueryResult implements \Iterator
{
    /**
     * @var string the amount of time to keep the scroll window open
     * (in ElasticSearch [time units](https://www.elastic.co/guide/en/elasticsearch/reference/current/common-options.html#time-units).
     */
    public $scrollWindow = '1m';

    /**
     * @var array the data retrieved in the current batch
     */
    private $_batch = null;
    /**
     * @var mixed the value for the current iteration
     */
    private $_value = null;
    /**
     * @var string|integer the key for the current iteration
     */
    private $_key = null;
    /*
     * @var string internal ElasticSearch scroll id
     */
    private $_lastScrollId = null;

    /**
     * @inheritdoc
     */
    public function reset()
    {
        if(isset($this->_lastScrollId)) {
            $this->query->createCommand($this->db)->clearScroll(['scroll_id' => $this->_lastScrollId]);
        }

        $this->_batch = null;
        $this->_value = null;
        $this->_key = null;
        $this->_lastScrollId = null;
    }

    /**
     * @inheritdoc
     */
    protected function fetchData()
    {
        if (null === $this->_lastScrollId) {
            //first query - do search
            $options = ['scroll' => $this->scrollWindow];
            if(!$this->query->orderBy) {
                $options['search_type'] = 'scan';
            }
            $result = $this->query->createCommand($this->db)->search($options);

            //if using "scan" mode, make another request immediately
            //(search request returned 0 results)
            if(!$this->query->orderBy) {
                $result = $this->query->createCommand($this->db)->scroll([
                    'scroll_id' => $result['_scroll_id'],
                    'scroll' => $this->scrollWindow,
                ]);
            }
        } else {
            //subsequent queries - do scroll
            $result = $this->query->createCommand($this->db)->scroll([
                'scroll_id' => $this->_lastScrollId,
                'scroll' => $this->scrollWindow,
            ]);
        }

        //get last scroll id
        $this->_lastScrollId = $result['_scroll_id'];

        //get data
        return $this->query->populate($result['hits']['hits']);
    }

}
