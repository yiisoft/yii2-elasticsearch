Using the ActiveRecord
======================

For general information on how to use yii's ActiveRecord please refer to the
[guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md).

For defining an Elasticsearch ActiveRecord class your record class needs to extend from [[yii\elasticsearch\ActiveRecord]]
and implement at least the [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]] method to define the attributes
of the record.

> NOTE: It is important NOT to include the primary key attribute (`_id`) in the attributes.

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // Other class attributes and methods go here
    // ...
    public function attributes()
    {
        return ['first_name', 'last_name', 'order_ids', 'email', 'registered_at', 'updated_at', 'status', 'is_active'];
    }
}
```

You may override [[yii\elasticsearch\ActiveRecord::index()|index()]] and [[yii\elasticsearch\ActiveRecord::type()|type()]]
to define the index and type this record represents.

> NOTE: Type is ignored for Elasticsearch 7.x and above. See [Data Mapping & Indexing](mapping-indexing.md) for more information.

Elasticsearch ActiveRecord is very similar to the database ActiveRecord as described in the
[guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md).

Most of its limitations and differences are derived from the [[yii\elasticsearch\Query]] implementation.


## Usage examples

```php
// Creating a new record
$customer = new Customer();
$customer->_id = 1; // setting primary keys is only allowed for new records
$customer->last_name = 'Doe'; // attributes can be set one by one
$customer->attributes = ['first_name' => 'Jane', 'email' => 'janedoe@example.com']; // or together
$customer->save();

// Getting records using the primary key
$customer = Customer::get(1); // get a record by pk
$customer = Customer::findOne(1); // also works
$customers = Customer::mget([1,2,3]); // get multiple records by pk
$customers = Customer::findAll([1, 2, 3]); // also works

// Finding records using simple conditions
$customer = Customer::find()->where(['first_name' => 'John', 'last_name' => 'Smith'])->one();

// Finding records using query DSL
// (see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html)
$articles = Article::find()->query(['match' => ['title' => 'yii']])->all();

$articles = Article::find()->query([
    'bool' => [
        'must' => [
            ['term' => ['is_active' => true]],
            ['terms' => ['email' => ['johnsmith@example.com', 'janedoe@example.com']]]
        ]
    ]
])->all();
```

## Primary keys

Unlike traditional SQL databases that let you define a primary key as any column or a set of columns, or even create a
table without a primary key, Elasticsearch stores the primary key separately from the rest of the document. The key is
not the part of the document structure and can not be changed once the document is saved into the index.

While Elasticsearch can create unique primary keys for new documents, it is also possible to specify them explicitly
for new records. Note that the key attribute is a string and is limited to 512 bytes. See
[Elasticsearch docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html)
for more information.

In elasticsearch, the name of the primary key is `_id`, and [[yii\elasticsearch\ActiveRecord]] provides getter and setter
methods to access it as a property. There is no need to add it to [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]].


## Foreign keys

SQL databases often use autoincremented integer columns as primary keys. When models from such databases are used in
relations in Elasticsearch models, those integers effectively become foreign keys.

Even though these keys are technically numeric, generally they should not be mapped as a numeric field datatype.
Elasticsearch optimizes numeric fields, such as integer or long, for range queries. However, keyword fields are better
for term and other term-level queries. Therefore it is recommended to use `keyword` field type for foreign keys. See
[Elasticsearch docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/keyword.html) for more information
on keyword fields.


## Defining relations

It is possible to define relations from Elasticsearch ActiveRecords to normal ActiveRecord classes and vice versa. However, [[yii\elasticsearch\ActiveQuery::via()|Via]]-relations can not be defined via a table as there are no tables in Elasticsearch.
You can only define relations via other records.

> **NOTE:** Elasticsearch limits the number of records returned by any query to 10 records by default.
> If you expect to get more records you should specify limit explicitly in query **and also** relation definition.
> This is also important for relations that use via() so that if via records are limited to 10
> the relations records can also not be more than 10.


## Scalar and array attributes

Any field in an Elasticsearch document [can hold multiple values](https://www.elastic.co/guide/en/elasticsearch/reference/current/array.html).
For example, if a customer mapping includes a keyword field for order ID, it is automatically possible to create
a document with one, two, or more order IDs. One can say that every field in a document is an array.

For consistency with [[yii\base\ActiveRecord]], when populating the record from data, single-item arrays are replaced
with the value they contain. However, it is possible to override this behavior by defining
[[yii\elasticsearch\ActiveRecord::arrayAttributes()|arrayAttributes()]].

```php
public function arrayAttributes()
{
    return ['order_ids'];
}
```

This way once fetched from the database, `$customer->order_ids` will be an array even if it contains one item,
e.g. `['AB-32162']`.


## Organizing complex queries

Any query can be composed using Elasticsearch's query DSL and passed to the [[yii\elasticsearch\Query::query()|query()]] method. However,
ES query DSL is notorious for its verbosity, and these oversized queries soon become unmanageable.

The usual approach with SQL ActiveRecord classes is to create scopes using methods in the query class that modify
the query itself. This does not work so well with Elasticsearch, so the recommended approach is to create static
functions that return building blocks of the query, then combine them.

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
        return ['range' => ['registered_at' => [
            'gte' => $dateFrom,
            'lte' => $dateTo,
        ]]];
    }
}

```

Now these sub-queries can be used to build the query.

```php
$customers = Customer::find()->query([
    'bool' => [
        'must' => [
            CustomerQuery::registrationDateRange('2016-01-01', '2016-01-20')
        ],
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

## Aggregations

[The aggregations framework](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html)
helps provide aggregated data based on a search query. It is based on simple building blocks called aggregations,
that can be composed in order to build complex summaries of the data.

As an example, let's determine how many customers registered each month.

```php
$searchResult = Customer::find()->addAggregate('customers_by_date', [
    'date_histogram' => [
        'field' => 'registered_at',
        'calendar_interval' => 'month',
    ],
])->limit(0)->search();

$customersByDate = ArrayHelper::map($searchResult['aggregations']['customers_by_date']['buckets'], 'key_as_string', 'doc_count');
```

Note that in this example [[yii\elasticsearch\ActiveQuery::search()|search()]] is used in place of
[[yii\elasticsearch\ActiveQuery::one()|one()]] or [[yii\elasticsearch\ActiveQuery::all()|all()]]. The `search()`
method returns not only the models, but also query metadata: shard statistics, aggregations, etc. When using aggregations,
the search results (hits) themselves often don't matter. That is why we're using
[[yii\elasticsearch\ActiveQuery::limit()|limit(0)]] to only return the metadata.

After some processing, `$customersByDate` contains data similar to this:
```php
[
    '2020-01-01' => 5,
    '2020-02-01' => 3,
    '2020-03-01' => 17,
]
```

## Suggesters

Sometimes it is necessary to suggest search terms that are similar to the search query and exist in the index.
For example, it might be useful to find known alternative spellings of a name. See the example below, and also
[Elasticsearch docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters.html) for details.

```php
$searchResult = Customer::find()->limit(0)
->addSuggester('customer_name', [
    'text' => 'Hans',
    'term' => [
        'field' => 'name',
    ]
])->search();

// Note that limit(0) will prevent the query from returning hits,
// so only suggestions are returned

$suggestions = ArrayHelper::map($searchResult["suggest"]["customer_name"], 'text', 'options');
$names = ArrayHelper::getColumn($suggestions['Hans'], 'text');
// $names == ['Hanns', 'Hannes', 'Hanse', 'Hansi']
```


## Unusual behavior of attributes with object mapping

The extension updates records using the `_update` endpoint. Since this endpoint is designed to perform partial updates
to documents, all attributes that have an "object" mapping type in Elasticsearch will be merged with existing data.
To demonstrate:

```php
$customer = new Customer();
$customer->my_attribute = ['foo' => 'v1', 'bar' => 'v2'];
$customer->save();
// at this point the value of my_attribute in Elasticsearch is {"foo": "v1", "bar": "v2"}

$customer->my_attribute = ['foo' => 'v3', 'bar' => 'v4'];
$customer->save();
// now the value of my_attribute in Elasticsearch is {"foo": "v3", "bar": "v4"}

$customer->my_attribute = ['baz' => 'v5'];
$customer->save();
// now the value of my_attribute in Elasticsearch is {"foo": "v3", "bar": "v4", "baz": "v5"}
// but $customer->my_attribute is still equal to ['baz' => 'v5']
```

Since this logic only applies to objects, the solution is to wrap the object into a single-element array. Since to
Elasticsearch a single-element array is the same thing as the element itself, there is no need to modify any other code.

```php
$customer->my_attribute = [['new' => 'value']]; // note the double brackets
$customer->save();
// now the value of my_attribute in Elasticsearch is {"new": "value"}
$customer->my_attribute = $customer->my_attribute[0]; // could be done for consistency
```

For more information see this discussion:
https://discuss.elastic.co/t/updating-an-object-field/110735
