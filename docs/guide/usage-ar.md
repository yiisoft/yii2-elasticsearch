Using the ActiveRecord
======================

For general information on how to use yii's ActiveRecord please refer to the [guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md).

For defining an elasticsearch ActiveRecord class your record class needs to extend from [[yii\elasticsearch\ActiveRecord]] and
implement at least the [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]] method to define the attributes of the record.
The handling of primary keys is different in elasticsearch as the primary key (the `_id` field in elasticsearch terms)
is not part of the attributes by default. However it is possible to define a [path mapping](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html)
for the `_id` field to be part of the attributes.
See [elasticsearch docs](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) on how to define it.
The `_id` field of a document/record can be accessed using [[yii\elasticsearch\ActiveRecord::getPrimaryKey()|getPrimaryKey()]] and
[[yii\elasticsearch\ActiveRecord::setPrimaryKey()|setPrimaryKey()]].
When path mapping is defined, the attribute name can be defined using the [[yii\elasticsearch\ActiveRecord::primaryKey()|primaryKey()]] method.

The following is an example model called `Customer`:

```php
class Customer extends \yii\elasticsearch\ActiveRecord
{
    /**
     * @return array the list of attributes for this record
     */
    public function attributes()
    {
        // path mapping for '_id' is setup to field 'id'
        return ['id', 'name', 'address', 'registration_date'];
    }

    /**
     * @return ActiveQuery defines a relation to the Order record (can be in other database, e.g. redis or sql)
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => 'id'])->orderBy('id');
    }

    /**
     * Defines a scope that modifies the `$query` to return only active(status = 1) customers
     */
    public static function active($query)
    {
        $query->andWhere(['status' => 1]);
    }
}
```

You may override [[yii\elasticsearch\ActiveRecord::index()|index()]] and [[yii\elasticsearch\ActiveRecord::type()|type()]]
to define the index and type this record represents.

The general usage of elasticsearch ActiveRecord is very similar to the database ActiveRecord as described in the
[guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md).
It supports the same interface and features except the following limitations and additions(*!*):

- As elasticsearch does not support SQL, the query API does not support `join()`, `groupBy()`, `having()` and `union()`.
  Sorting, limit, offset and conditional where are all supported.
- [[yii\elasticsearch\ActiveQuery::from()|from()]] does not select the tables, but the
  [index](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index)
  and [type](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) to query against.
- `select()` has been replaced with [[yii\elasticsearch\ActiveQuery::fields()|fields()]] which basically does the same but
  `fields` is more elasticsearch terminology.
  It defines the fields to retrieve from a document.
- [[yii\elasticsearch\ActiveQuery::via()|via]]-relations can not be defined via a table as there are no tables in elasticsearch. You can only define relations via other records.
- As elasticsearch is not only a data storage but also a search engine there is of course support added for searching your records.
  There are
  [[yii\elasticsearch\ActiveQuery::query()|query()]],
  [[yii\elasticsearch\ActiveQuery::filter()|filter()]] and
  [[yii\elasticsearch\ActiveQuery::addFacet()|addFacet()]] methods that allows to compose an elasticsearch query.
  See the usage example below on how they work and check out the [Query DSL](http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html)
  on how to compose `query` and `filter` parts.
- It is also possible to define relations from elasticsearch ActiveRecords to normal ActiveRecord classes and vice versa.

> **NOTE:** elasticsearch limits the number of records returned by any query to 10 records by default.
> If you expect to get more records you should specify limit explicitly in query **and also** relation definition.
> This is also important for relations that use via() so that if via records are limited to 10
> the relations records can also not be more than 10.


Usage example:

```php
$customer = new Customer();
$customer->primaryKey = 1; // in this case equivalent to $customer->id = 1;
$customer->attributes = ['name' => 'test'];
$customer->save();

$customer = Customer::get(1); // get a record by pk
$customers = Customer::mget([1,2,3]); // get multiple records by pk
$customer = Customer::find()->where(['name' => 'test'])->one(); // find by query, note that you need to configure mapping for this field in order to find records properly
$customers = Customer::find()->active()->all(); // find all by query (using the `active` scope)

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
$result = Article::find()->query(["match" => ["title" => "yii"]])->all(); // articles whose title contains "yii"

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
$query = Article::find()->query([
    "fuzzy_like_this" => [
        "fields" => ["title", "description"],
        "like_text" => "This query will return articles that are similar to this text :-)",
        "max_query_terms" => 12
    ]
]);

$query->all(); // gives you all the documents
// you can add facets to your search:
$query->addStatisticalFacet('click_stats', ['field' => 'visit_count']);
$query->search(); // gives you all the records + stats about the visit_count field. e.g. mean, sum, min, max etc...
```

## Complex queries

Any query can be composed using ElasticSearch's query DSL and passed to the `ActiveRecord::query()` method. However, ES query DSL is notorious for its verbosity, and these oversized queries soon become unmanageable.
Here is a method to make queries more maintainable. Start by defining a query class just as it is done for SQL-based `ActiveRecord`.

```php
class CustomerQuery extends ActiveQuery
{
    public static function name($name)
    {
        return ['match' => ['name' => $name]];
    }

    public static function address($address)
    {
        return ['match' => ['address' => $address]];
    }

    public static function registrationDateRange($dateFrom, $dateTo)
    {
        return ['range' => ['registration_date' => [
            'gte' => $dateFrom,
            'lte' => $dateTo,
        ]]];
    }
}

```

Now you can use these query components to assemble the resulting query and/or filter.

```php
$customers = Customer::find()->filter([
    CustomerQuery::registrationDateRange('2016-01-01', '2016-01-20'),
])->query([
    'bool' => [
        'should' => [
            CustomerQuery::name('John'),
            CustomerQuery::address('London'),
        ],
        'must_not' => [
            CustomerQuery::name('Jack'),
        ],
    ],
])->all();
```

## Aggregation

[The aggregations framework](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html) helps provide aggregated data based on a search query. It is based on simple building blocks called aggregations, that can be composed in order to build complex summaries of the data.  

Using the previously defined `Customer` class, let's find out how many customers have registered each day. To do that we use the `terms` aggregation.


```php
$aggData = Customer::find()->addAggregation('customers_by_date', 'terms', [
    'field' => 'registration_date',
    'order' => ['_count' => 'desc'],
    'size' => 10, //top 10 registration dates
])->search(null, ['search_type' => 'count']);

```                    

In this example we are specifically requesting aggregation results only. The following code further process the data.

```php
$customersByDate = ArrayHelper::map($aggData['aggregations']['customers_by_date']['buckets'], 'key', 'doc_count');
```

Now `$customersByDate` contains 10 dates that correspond to the the highest number of users registered.
