# マッピングとインデクシング

## SQL との比較

[Elasticsearch のドキュメント](https://www.elastic.co/guide/en/elasticsearch/reference/current/_mapping_concepts_across_sql_and_elasticsearch.html) が Elasticsearch と SQL の概念について広範囲にわたって解説しています。
私たちは基本に注力しましょう。

Elasticsearch クラスタは一つ以上の Elasticsearch インスタンスから構成されます。リクエストはそのうちの一つのインスタンスに送られます。
which propagates the query to other instances in the cluster, collects results, and then returns them to the client.
Therefore a cluster or an instance that represents it roughly correspond to a SQL database.

In Elasticsearch data is stored in indices. An index corresponds to a SQL table.

An index contains documents. Documents correspond to rows in a SQL table. In this extension, an
[[yii\elasticsearch\ActiveRecord|ActiveRecord]] represents a document in an index. The operation of saving a document
into an index is called indexing.

The schema or structure of a document is defined in the so-called mapping. A mapping defines document fields, which
correspond to columns in SQL. In Elasticsearch the primary key field is special, because it always exists and its
name and structure can not be changed. Other fields are fully configurable.


## Mapping fields beforehand

Even though new fields will be created on the fly when documents are indexed, it is considered good practice
to define a mapping before indexing documents.

Generally, once an attribute is defined, it is not possible to change its type. For example if a text field is configured to use the English language analyzer, it is not possible to switch to a different language without reindexing every document in the index.
Certain limited modifications to mapping can be applied on the fly. See
[Elasticsearch documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html#updating-field-mappings)
for more info.


## Document types

Originally, Elasticsearch was designed to store documents with different structure in the same index. To handle this,
a concept of "type" was introduced. However, this approach soon fell out of favor. As a result, types have been
[removed from Elasticsearch 7.x](https://www.elastic.co/guide/en/elasticsearch/reference/current/removal-of-types.html).

Currently, best practice is to have only one type per index. Technically, if the extension is configured for
Elasticsearch 7 or above, [[yii\elasticsearch\ActiveRecord::type()|type()]] is ignored, and implicitly replaced with
`_doc` where required by the API.


## Creating helper methods

Our recommendation is to create several static methods in your [[yii\elasticsearch\ActiveRecord|ActiveRecord]] model
that deal with index creation and updates. Here is one example of how this can be done.

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // Other class attributes and methods go here
    // ...

    /**
     * @return array This model's mapping
     */
    public static function mapping()
    {
        return [
            // Field types: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#field-datatypes
            'properties' => [
                'first_name'     => ['type' => 'text'],
                'last_name'      => ['type' => 'text'],
                'order_ids'      => ['type' => 'keyword'],
                'email'          => ['type' => 'keyword'],
                'registered_at'  => ['type' => 'date'],
                'updated_at'     => ['type' => 'date'],
                'status'         => ['type' => 'keyword'],
                'is_active'      => ['type' => 'boolean'],
            ]
        ];
    }

    /**
     * Set (update) mappings for this model
     */
    public static function updateMapping()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->setMapping(static::index(), static::type(), static::mapping());
    }

    /**
     * Create this model's index
     */
    public static function createIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->createIndex(static::index(), [
            //'aliases' => [ /* ... */ ],
            'mappings' => static::mapping(),
            //'settings' => [ /* ... */ ],
        ]);
    }

    /**
     * Delete this model's index
     */
    public static function deleteIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->deleteIndex(static::index(), static::type());
    }
}
```

To create the index with proper mappings, call `Customer::createIndex()`. If you have changed the mapping in a way that
allows mapping update (e.g. created a new property), call `Customer::updateMapping()`.

However, if you have changed a property (e.g. went from `string` to `date`), Elasticsearch will not be able to update
the mapping. In this case you need to delete your index (by calling `Customer::deleteIndex()`), create it anew with updated
mapping (by calling `Customer::createIndex()`), and then repopulate it with data.


## インデックスとマッピングを生成する

Elasticsearch のマッピングを漸進的に更新することは常に可能であるとは限りません。ですから、あなたのモデルの中に、インデックスの生成と更新を扱ういくつかの静的なメソッドを作っておくというのは、良いアイデアです。どのようにすればそれが出来るかの一例を次に示します。

```php
Class Book extends yii\elasticsearch\ActiveRecord
{
    // クラスのその他の属性とメソッド
    // ...

    /**
     * @return array このモデルのマッピングを返す
     */
    public static function mapping()
    {
        return [
            static::type() => [
                'properties' => [
                    'name'           => ['type' => 'text'],
                    'author_name'    => ['type' => 'text'],
                    'publisher_name' => ['type' => 'text'],
                    'created_at'     => ['type' => 'long'],
                    'updated_at'     => ['type' => 'long'],
                    'status'         => ['type' => 'long'],
                ]
            ],
        ];
    }

    /**
     * このモデルのマッピングを設定(更新)する
     */
    public static function updateMapping()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->setMapping(static::index(), static::type(), static::mapping());
    }

    /**
     * このモデルのインデックスを生成する
     */
    public static function createIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->createIndex(static::index(), [
            //'settings' => [ /* ... */ ],
            'mappings' => static::mapping(),
            //'warmers' => [ /* ... */ ],
            //'aliases' => [ /* ... */ ],
            //'creation_date' => '...'
        ]);
    }

    /**
     * このモデルのインデックスを削除する
     */
    public static function deleteIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->deleteIndex(static::index(), static::type());
    }
}
```

適切なマッピングでインデックスを生成するためには、`Book::createIndex()` を呼びます。マッピングの更新を許すような仕方でマッピングを変更した場合 (例えば、新しいプロパティを作成した場合など) は、`Book::updateMapping()` を呼びます。

しかし、プロパティを変更した場合 (例えば、`string` から `date` に変えた場合など) は、Elasticsearch はマッピングを更新することが出来ません。この場合は、インデックスを削除し (`Book::deleteIndex()` を呼びます)、更新されたマッピングでインデックスを新規に作成し (`Book::createIndex()` を呼びます)、そして、データを投入しなければなりません。

## インデクシング
TBD
