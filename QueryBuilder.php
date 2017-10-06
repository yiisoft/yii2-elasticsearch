<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\helpers\Json;

/**
 * QueryBuilder builds an elasticsearch query based on the specification given as a [[Query]] object.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class QueryBuilder extends \yii\base\Object
{
    /**
     * @var Connection the database connection.
     */
    public $db;


    /**
     * Constructor.
     * @param Connection $connection the database connection.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($connection, $config = [])
    {
        $this->db = $connection;
        parent::__construct($config);
    }

    /**
     * Generates query from a [[Query]] object.
     * @param Query $query the [[Query]] object from which the query will be generated
     * @return array the generated SQL statement (the first array element) and the corresponding
     * parameters to be bound to the SQL statement (the second array element).
     */
    public function build($query)
    {
        $parts = [];

        if ($query->storedFields !== null) {
            $parts['stored_fields'] = $query->storedFields;
        }
        if ($query->scriptFields !== null) {
            $parts['script_fields'] = $query->scriptFields;
        }

        if ($query->source !== null) {
            $parts['_source'] = $query->source;
        }
        if ($query->limit !== null && $query->limit >= 0) {
            $parts['size'] = $query->limit;
        }
        if ($query->offset > 0) {
            $parts['from'] = (int)$query->offset;
        }
        if (isset($query->minScore)) {
            $parts['min_score'] = (float)$query->minScore;
        }
        if (isset($query->explain)) {
            $parts['explain'] = $query->explain;
        }

        $whereQuery = $this->buildQueryFromWhere($query->where);
        if ($whereQuery) {
            $parts['query'] = $whereQuery;
        } else if ($query->query) {
            $parts['query'] = $query->query;
        }

        if (!empty($query->highlight)) {
            $parts['highlight'] = $query->highlight;
        }
        if (!empty($query->aggregations)) {
            $parts['aggregations'] = $query->aggregations;
        }
        if (!empty($query->stats)) {
            $parts['stats'] = $query->stats;
        }
        if (!empty($query->suggest)) {
            $parts['suggest'] = $query->suggest;
        }
        if (!empty($query->postFilter)) {
            $parts['post_filter'] = $query->postFilter;
        }

        $sort = $this->buildOrderBy($query->orderBy);
        if (!empty($sort)) {
            $parts['sort'] = $sort;
        }

        $options = $query->options;
        if ($query->timeout !== null) {
            $options['timeout'] = $query->timeout;
        }

        return [
            'queryParts' => $parts,
            'index' => $query->index,
            'type' => $query->type,
            'options' => $options,
        ];
    }

    /**
     * adds order by condition to the query
     */
    public function buildOrderBy($columns)
    {
        if (empty($columns)) {
            return [];
        }
        $orders = [];
        foreach ($columns as $name => $direction) {
            if (is_string($direction)) {
                $column = $direction;
                $direction = SORT_ASC;
            } else {
                $column = $name;
            }
            if ($column == '_id') {
                $column = '_uid';
            }

            // allow elasticsearch extended syntax as described in http://www.elastic.co/guide/en/elasticsearch/guide/master/_sorting.html
            if (is_array($direction)) {
                $orders[] = [$column => $direction];
            } else {
                $orders[] = [$column => ($direction === SORT_DESC ? 'desc' : 'asc')];
            }
        }

        return $orders;
    }

    public function buildQueryFromWhere($condition) {
        $where = $this->buildCondition($condition);
        if ($where) {
            $query = [
                'constant_score' => [
                    'filter' => $where,
                ],
            ];
            return $query;
        } else {
            return null;
        }
    }

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param string|array $condition the condition specification. Please refer to [[Query::where()]] on how to specify a condition.
     * @throws \yii\base\InvalidParamException if unknown operator is used in query
     * @throws \yii\base\NotSupportedException if string conditions are used in where
     * @return string the generated SQL expression
     */
    public function buildCondition($condition)
    {
        static $builders = [
            'not' => 'buildNotCondition',
            'and' => 'buildBoolCondition',
            'or' => 'buildBoolCondition',
            'between' => 'buildBetweenCondition',
            'not between' => 'buildBetweenCondition',
            'in' => 'buildInCondition',
            'not in' => 'buildInCondition',
            'like' => 'buildLikeCondition',
            'not like' => 'buildLikeCondition',
            'or like' => 'buildLikeCondition',
            'or not like' => 'buildLikeCondition',
            'lt' => 'buildHalfBoundedRangeCondition',
            '<' => 'buildHalfBoundedRangeCondition',
            'lte' => 'buildHalfBoundedRangeCondition',
            '<=' => 'buildHalfBoundedRangeCondition',
            'gt' => 'buildHalfBoundedRangeCondition',
            '>' => 'buildHalfBoundedRangeCondition',
            'gte' => 'buildHalfBoundedRangeCondition',
            '>=' => 'buildHalfBoundedRangeCondition',
        ];

        if (empty($condition)) {
            return [];
        }
        if (!is_array($condition)) {
            throw new NotSupportedException('String conditions in where() are not supported by elasticsearch.');
        }
        if (isset($condition[0])) { // operator format: operator, operand 1, operand 2, ...
            $operator = strtolower($condition[0]);
            if (isset($builders[$operator])) {
                $method = $builders[$operator];
                array_shift($condition);

                return $this->$method($operator, $condition);
            } else {
                throw new InvalidParamException('Found unknown operator in query: ' . $operator);
            }
        } else { // hash format: 'column1' => 'value1', 'column2' => 'value2', ...

            return $this->buildHashCondition($condition);
        }
    }

    private function buildHashCondition($condition)
    {
        $parts = $emptyFields = [];
        foreach ($condition as $attribute => $value) {
            if ($attribute == '_id') {
                if ($value === null) { // there is no null pk
                    $parts[] = ['terms' => ['_uid' => []]]; // this condition is equal to WHERE false
                } else {
                    $parts[] = ['ids' => ['values' => is_array($value) ? $value : [$value]]];
                }
            } else {
                if (is_array($value)) { // IN condition
                    $parts[] = ['terms' => [$attribute => $value]];
                } else {
                    if ($value === null) {
                        $emptyFields[] = [ 'exists' => [ 'field' => $attribute ] ];
                    } else {
                        $parts[] = ['term' => [$attribute => $value]];
                    }
                }
            }
        }

        $query = [ 'must' => $parts ];
        if ($emptyFields) {
            $query['must_not'] = $emptyFields;
        }
        return [ 'bool' => $query ];
    }

    private function buildNotCondition($operator, $operands)
    {
        if (count($operands) != 1) {
            throw new InvalidParamException("Operator '$operator' requires exactly one operand.");
        }

        $operand = reset($operands);
        if (is_array($operand)) {
            $operand = $this->buildCondition($operand);
        }

        return [
            'bool' => [
                'must_not' => $operand,
            ],
        ];
    }

    private function buildBoolCondition($operator, $operands)
    {
        $parts = [];
        if ($operator === 'and') {
            $clause = 'must';
        } else if ($operator === 'or') {
            $clause = 'should';
        } else {
            throw new InvalidParamException("Operator should be 'or' or 'and'");
        }

        foreach ($operands as $operand) {
            if (is_array($operand)) {
                $operand = $this->buildCondition($operand);
            }
            if (!empty($operand)) {
                $parts[] = $operand;
            }
        }
        if ($parts) {
            return [
                'bool' => [
                    $clause => $parts,
                ]
            ];
        } else {
            return null;
        }
    }

    private function buildBetweenCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidParamException("Operator '$operator' requires three operands.");
        }

        list($column, $value1, $value2) = $operands;
        if ($column === '_id') {
            throw new NotSupportedException('Between condition is not supported for the _id field.');
        }
        $filter = ['range' => [$column => ['gte' => $value1, 'lte' => $value2]]];
        if ($operator === 'not between') {
            $filter = ['bool' => ['must_not'=>$filter]];
        }

        return $filter;
    }

    private function buildInCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1]) || !is_array($operands)) {
            throw new InvalidParamException("Operator '$operator' requires array of two operands: column and values");
        }

        list($column, $values) = $operands;

        $values = (array)$values;

        if (empty($values) || $column === []) {
            return $operator === 'in' ? ['terms' => ['_uid' => []]] : []; // this condition is equal to WHERE false
        }

        if (count($column) > 1) {
            return $this->buildCompositeInCondition($operator, $column, $values);
        } elseif (is_array($column)) {
            $column = reset($column);
        }
        $canBeNull = false;
        foreach ($values as $i => $value) {
            if (is_array($value)) {
                $values[$i] = $value = isset($value[$column]) ? $value[$column] : null;
            }
            if ($value === null) {
                $canBeNull = true;
                unset($values[$i]);
            }
        }
        if ($column === '_id') {
            if (empty($values) && $canBeNull) { // there is no null pk
                $filter = ['terms' => ['_uid' => []]]; // this condition is equal to WHERE false
            } else {
                $filter = ['ids' => ['values' => array_values($values)]];
                if ($canBeNull) {
                    $filter = [
                        'bool' => [
                            'should' => [
                                $filter,
                                'bool' => ['must_not' => ['exists' => ['field'=>$column]]],
                            ],
                        ],
                    ];
                }
            }
        } else {
            if (empty($values) && $canBeNull) {
                $filter = [
                    'bool' => [
                        'must_not' => [
                            'exists' => [ 'field' => $column ],
                        ]
                    ]
                ];
            } else {
                $filter = [ 'terms' => [$column => array_values($values)] ];
                if ($canBeNull) {
                    $filter = [
                        'bool' => [
                            'should' => [
                                $filter,
                                'bool' => ['must_not' => ['exists' => ['field'=>$column]]],
                            ],
                        ],
                    ];
                }
            }
        }

        if ($operator === 'not in') {
            $filter = [
                'bool' => [
                    'must_not' => $filter,
                ],
            ];
        }

        return $filter;
    }

    /**
     * Builds a half-bounded range condition
     * (for "gt", ">", "gte", ">=", "lt", "<", "lte", "<=" operators)
     * @param string $operator
     * @param array $operands
     * @return array Filter expression
     */
    private function buildHalfBoundedRangeCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidParamException("Operator '$operator' requires two operands.");
        }

        list($column, $value) = $operands;
        if ($column === '_id') {
            $column = '_uid';
        }

        $range_operator = null;

        if (in_array($operator, ['gte', '>='])) {
            $range_operator = 'gte';
        } elseif (in_array($operator, ['lte', '<='])) {
            $range_operator = 'lte';
        } elseif (in_array($operator, ['gt', '>'])) {
            $range_operator = 'gt';
        } elseif (in_array($operator, ['lt', '<'])) {
            $range_operator = 'lt';
        }

        if ($range_operator === null) {
            throw new InvalidParamException("Operator '$operator' is not implemented.");
        }

        $filter = [
            'range' => [
                $column => [
                    $range_operator => $value
                ]
            ]
        ];

        return $filter;
    }

    protected function buildCompositeInCondition($operator, $columns, $values)
    {
        throw new NotSupportedException('composite in is not supported by elasticsearch.');
    }

    private function buildLikeCondition($operator, $operands)
    {
        throw new NotSupportedException('like conditions are not supported by elasticsearch.');
    }
}
