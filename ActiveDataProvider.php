<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use yii\base\InvalidConfigException;

/**
 * ActiveDataProvider is an enhanced version of [[\yii\data\ActiveDataProvider]] specific to the ElasticSearch.
 * It allows to fetch not only rows and total rows count, but full query results including aggregations and so on.
 *
 * @property array $queryResults the query results.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0.5
 */
class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    /**
     * @var array the full query results.
     */
    private $_queryResults;


    /**
     * @param array $results full query results
     */
    public function setQueryResults($results)
    {
        $this->_queryResults = $results;
    }

    /**
     * @return array full query results
     */
    public function getQueryResults()
    {
        if (!is_array($this->_queryResults)) {
            $this->prepare();
        }
        return $this->_queryResults;
    }

    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        if (!$this->query instanceof Query) {
            throw new InvalidConfigException('The "query" property must be an instance "' . Query::className() . '" or its subclasses.');
        }

        $query = clone $this->query;
        if (($pagination = $this->getPagination()) !== false) {
            // pagination fails to validate page number, because total count is unknown at this stage
            $pagination->validatePage = false;
            $query->limit($pagination->getLimit())->offset($pagination->getOffset());
        }
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        }

        $results = $query->search($this->db);
        $this->setQueryResults($results);

        if ($pagination !== false) {
            $pagination->totalCount = $this->getTotalCount();
        }

        return $results['hits']['hits'];
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof Query) {
            throw new InvalidConfigException('The "query" property must be an instance "' . Query::className() . '" or its subclasses.');
        }

        $results = $this->getQueryResults();
        return (int)$results['hits']['total'];
    }
}