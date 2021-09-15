# Using the Query

The [[yii\elasticsearch\Query]] class is generally compatible with its [[yii\db\Query|parent query class]], well-described in the
[guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-query-builder.md).

The differences are outlined below.

- As Elasticsearch does not support SQL, the query API does not support `join()`, `groupBy()`, `having()`, and `union()`.
  Sorting, `limit()`, `offset()`, `limit()`, and `where()` are all supported (with certain limitations).

- [[yii\elasticsearch\Query::from()|from()]] does not select the tables, but the
  [index](https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index)
  and [type](https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) to query against.

- `select()` has been replaced with [[yii\elasticsearch\Query::storedFields()|storedFields()]]. It defines the fields
  to retrieve from a document, similar to columns in SQL.

- As Elasticsearch is not only a database but also a search engine, additional query and aggregation mechanisms are supported.
Check out the [Query DSL](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html) on how to compose queries.


## Executing queries

The [[yii\elasticsearch\Query]] class provides the usual methods for executing queries: [[yii\elasticsearch\Query::one()|one()]] and
[[yii\elasticsearch\Query::all()|all()]]. They return only the search results (or a single result).

There is also the [[yii\elasticsearch\Query::search()|search()]] method that returns both the search results, and all of the
metadata retrieved from Elasticsearch, including aggregations.

The extension fully supports the highly efficient scroll mode, that allows to retrieve large results sets. See
[[yii\elasticsearch\Query::batch()|batch()]] and [[yii\elasticsearch\Query::each()|each()]] for more information.


## Number of returned records and pagination caveats

Unlike most SQL servers that will return all results unless a `LIMIT` clause is provided, Elasticsearch limits the result
set to 10 records by default. To get more records, use [[yii\elasticsearch\Query::limit()|limit()]]. This is especially important
when defining relations in [[yii\elasticsearch\ActiveRecord|ActiveRecord]], where record limit needs to be specified
explicitly.

Elasticsearch is generally poor suited to tasks that require deep pagination. It is optimized for search engine behavior,
where only first few pages of results have any relevance. While it is technically possible to go far into the result set using
[[yii\elasticsearch\Query::limit()|limit()]] and [[yii\elasticsearch\Query::offset()|offset()]], performance is reduced.

One possible solution would be to use the scroll mode, which behaves similar to cursors in traditional SQL databases. Scroll mode
is implemented with [[yii\elasticsearch\Query::batch()|batch()]] and [[yii\elasticsearch\Query::each()|each()]] methods.


## Error handling in queries

Elasticsearch is a distributed database. Because of its distributed nature, certain requests may be partially successful.

Consider how a typical search is performed. The query is sent to all relevant shards, then their results are collected,
processed, and returned to user. It is possible that not all shards are able to return a result. Yet, even with some data
missing, the result may be useful.

With every query the server returns [some additional metadata](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-search.html#search-api-response-body),
including data on which shards failed. This data is lost when using standard Yii2 methods like
[[yii\elasticsearch\Query::one()|one()]] and [[yii\elasticsearch\Query::all()|all()]].
Even if some shards failed, it is not considered a server error.

To get extended data, including shard statictics, use the [[yii\elasticsearch\Query::search()|search()]] method.

The query itself can also fail for a number of reasons (connectivity issues, syntax error, etc.) but that will result
in an exception.


## Error handling in bulk requests

In Elasticsearch a [bulk request](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html) performs
multiple operations in a single API call. This reduces overhead and can greatly increase indexing speed.

The operations are executed individually, so some can be successful, while others fail. Having some of the operations fail
does not cause the whole bulk request to fail. If it is important to know if any of the constituent operations failed,
the [[yii\elasticsearch\BulkCommand::execute()|result of the bulk request]] needs to be checked.

The bulk request itself can also fail, for example, because of connectivity issues, but that will result in an exception.


## Document counts in ES > 7.0.0

As of Elasticsearch 7.0.0, for result sets over 10 000 hits, document counts (`total_hits`) are [no longer exact by
default](https://www.elastic.co/guide/en/elasticsearch/reference/current/breaking-changes-7.0.html#track-total-hits-10000-default).
In other words, if the result set contains more than 10 000 documents, `total_hits` is reported as 10 000, and if it is less,
then it is reported exactly. This results in a performance improvement.

The `track_total_hits` option can be used to change this behavior. If it is set to `'true'`, exact document count
will always be returned, and an integer value overrides the default threshold value of 10 000.

```
$query = new Query();
$query->from('customer');

// Note the literal string 'true', not a boolean value!
$query->addOptions(['track_total_hits' => 'true']);
```

## Runtime Fields/Mappings in ES >= 7.11

Runtime Fields are fields that can be dynamically generated at query time by supplying a script similar to `script_fields`.
The major difference being that the value of a Runtime Field can be used in search queries, aggregations, filtering, and 
sorting.

Any Runtime Field values that you want to be included in the search results must be added to the `field` array by passing
an array of field names using the `fields()` method. 

Example for fetching users' full names by concatenating the `first_name` and `last_name` fields from the index and 
sorting them alphabetically.
```php
$results = (new yii\elasticsearch\Query())
    ->from('users')
    ->runtimeMappings([
        'full_name' => [
            'type' => 'keyword',
            'script' => "emit(doc['first_name'].value + ' ' + doc['last_name'].value)",
        ],
    ])
    ->fields(['full_name'])
    ->orderBy(['full_name' => SORT_ASC])
    ->search($connection);
```

For more information concerning `type` and `script` please see [Elastic's Runtime Field Documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/runtime.html)
