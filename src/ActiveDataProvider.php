<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;

/**
 * ActiveDataProvider is an enhanced version of [[\yii\data\ActiveDataProvider]] specific to the Elasticsearch.
 * It allows to fetch not only rows and total rows count, but full query results including aggregations and so on.
 *
 * Note: this data provider fetches result models and total count using single Elasticsearch query, so results total
 * count will be fetched after pagination limit applying, which eliminates ability to verify if requested page number
 * actually exist. Data provider disables [[yii\data\Pagination::$validatePage]] automatically because of this.
 *
 * @property-read array $aggregations All aggregations results.
 * @property array $queryResults Full query results.
 * @property-read array $suggestions All suggestions results.
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
     * @return array all aggregations results
     */
    public function getAggregations()
    {
        $results = $this->getQueryResults();
        return isset($results['aggregations']) ? $results['aggregations'] : [];
    }

    /**
     * Returns results of the specified aggregation.
     * @param string $name aggregation name.
     * @return array aggregation results.
     * @throws InvalidCallException if query results do not contain the requested aggregation.
     */
    public function getAggregation($name)
    {
        $aggregations = $this->getAggregations();
        if (!isset($aggregations[$name])) {
            throw new InvalidCallException("Aggregation '{$name}' not found.");
        }
        return $aggregations[$name];
    }

    /**
     * @return array all suggestions results
     */
    public function getSuggestions()
    {
        $results = $this->getQueryResults();
        return isset($results['suggest']) ? $results['suggest'] : [];
    }

    /**
     * Returns results of the specified suggestion.
     * @param string $name suggestion name.
     * @return array suggestion results.
     * @throws InvalidCallException if query results do not contain the requested suggestion.
     */
    public function getSuggestion($name)
    {
        $suggestions = $this->getSuggestions();
        if (!isset($suggestions[$name])) {
            throw new InvalidCallException("Suggestion '{$name}' not found.");
        }
        return $suggestions[$name];
    }

    /**
     * {@inheritdoc}
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

        if (is_array(($results = $query->search($this->db)))) {
            $this->setQueryResults($results);
            return $results['hits']['hits'];
        }
        $this->setQueryResults([]);
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof Query) {
            throw new InvalidConfigException('The "query" property must be an instance "' . Query::className() . '" or its subclasses.');
        }

        $results = $this->getQueryResults();
        if (isset($results['hits']['total'])) {
            return is_array($results['hits']['total']) ? (int) $results['hits']['total']['value'] : (int) $results['hits']['total'];
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareKeys($models)
    {
        $keys = [];
        if ($this->key !== null) {
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }

            return $keys;
        } elseif ($this->query instanceof ActiveQueryInterface) {
            /* @var $class \yii\elasticsearch\ActiveRecord */
            $class = $this->query->modelClass;
            foreach ($models as $model) {
                $keys[] = $model->primaryKey;
            }
            return $keys;
        } else {
            return array_keys($models);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        parent::refresh();
        $this->_queryResults = null;
    }
}
