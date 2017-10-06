<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use yii\base\Object;

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
class BatchQueryResult extends Object implements \Iterator
{
    /**
     * @var Connection the DB connection to be used when performing batch query.
     * If null, the `elasticsearch` application component will be used.
     */
    public $db;
    /**
     * @var Query the query object associated with this batch query.
     * Do not modify this property directly unless after [[reset()]] is called explicitly.
     */
    public $query;
    /**
     * @var boolean whether to return a single row during each iteration.
     * If false, a whole batch of rows will be returned in each iteration.
     */
    public $each = false;
    /**
     * @var DataReader the data reader associated with this batch query.
     */
    private $_dataReader;
    /**
     * @var array the data retrieved in the current batch
     */
    private $_batch;
    /**
     * @var mixed the value for the current iteration
     */
    private $_value;
    /**
     * @var string|integer the key for the current iteration
     */
    private $_key;
    /**
     * @var string the amount of time to keep the scroll window open
     * (in ElasticSearch [time units](https://www.elastic.co/guide/en/elasticsearch/reference/current/common-options.html#time-units).
     */
    public $scrollWindow = '1m';

    /*
     * @var string internal ElasticSearch scroll id
     */
    private $_lastScrollId = null;


    /**
     * Destructor.
     */
    public function __destruct()
    {
        // make sure cursor is closed
        $this->reset();
    }

    /**
     * Resets the batch query.
     * This method will clean up the existing batch query so that a new batch query can be performed.
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
     * Resets the iterator to the initial state.
     * This method is required by the interface [[\Iterator]].
     */
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    /**
     * Moves the internal pointer to the next dataset.
     * This method is required by the interface [[\Iterator]].
     */
    public function next()
    {
        if ($this->_batch === null || !$this->each || $this->each && next($this->_batch) === false) {
            $this->_batch = $this->fetchData();
            reset($this->_batch);
        }

        if ($this->each) {
            $this->_value = current($this->_batch);
            if ($this->query->indexBy !== null) {
                $this->_key = key($this->_batch);
            } elseif (key($this->_batch) !== null) {
                $this->_key++;
            } else {
                $this->_key = null;
            }
        } else {
            $this->_value = $this->_batch;
            $this->_key = $this->_key === null ? 0 : $this->_key + 1;
        }
    }

    /**
     * Fetches the next batch of data.
     * @return array the data fetched
     */
    protected function fetchData()
    {
        if (null === $this->_lastScrollId) {
            //first query - do search
            $options = ['scroll' => $this->scrollWindow];
            if(!$this->query->orderBy) {
                $query = clone $this->query;
                $query->orderBy('_doc');
                $cmd = $this->query->createCommand($this->db);
            } else {
                $cmd = $this->query->createCommand($this->db);
            }
            $result = $cmd->search($options);
            if ($result === false) {
                throw new Exception('Elasticsearch search query failed.');
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

    /**
     * Returns the index of the current dataset.
     * This method is required by the interface [[\Iterator]].
     * @return integer the index of the current row.
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     * Returns the current dataset.
     * This method is required by the interface [[\Iterator]].
     * @return mixed the current dataset.
     */
    public function current()
    {
        return $this->_value;
    }

    /**
     * Returns whether there is a valid dataset at the current position.
     * This method is required by the interface [[\Iterator]].
     * @return boolean whether there is a valid dataset at the current position.
     */
    public function valid()
    {
        return !empty($this->_batch);
    }
}
