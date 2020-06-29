Using the Query
===============

The [[yii\elasticsearch\Query]] class is generally compatible with its [[yii\db\Query|parent query class]], well-described in the
[guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-query-builder.md).

The differences are outlined below.

- As Elasticsearch does not support SQL, the query API does not support `join()`, `groupBy()`, `having()` and `union()`.
  Sorting, limit, offset and conditional where are all supported (with certain limitations).

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

There is also the [[yii\elasticsearch\Query::search()|search()]] method that returns all of the metadata retrieved from Elasticsearch,
including aggregations.

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
