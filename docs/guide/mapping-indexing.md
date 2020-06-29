Mapping & Indexing
==================


## Creating indices and mappings

Elasticsearch is a document store, and the schema of those documents is called a mapping. Every index should have a
mapping. Even though new fields will be created on the fly when documents are indexed, it is considered good practice
to define a mapping before indexing documents.

Generally, once an attribute is defined, it is not possible to change its type (for example, go from integer to string).
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
