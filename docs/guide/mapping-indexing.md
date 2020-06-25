Mapping & Indexing
==================

## Creating index and mapping

Since it is not always possible to update Elasticsearch mappings incrementally, it is a good idea to create several static methods in your model that deal with index creation and updates. Here is one example of how this can be done.

```php
class Book extends yii\elasticsearch\ActiveRecord
{
    // Other class attributes and methods go here
    // ...

    /**
     * @return array This model's mapping
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
            //'settings' => [ /* ... */ ],
            'mappings' => static::mapping(),
            //'warmers' => [ /* ... */ ],
            //'aliases' => [ /* ... */ ],
            //'creation_date' => '...'
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

To create the index with proper mappings, call `Book::createIndex()`. If you have changed the mapping in a way that allows mapping update (e.g. created a new property), call `Book::updateMapping()`.

However, if you have changed a property (e.g. went from `string` to `date`), Elasticsearch will not be able to update the mapping. In this case you need to delete your index (by calling `Book::deleteIndex()`), create it anew with updated mapping (by calling `Book::createIndex()`), and then repopulate it with data.  

## Indexing
TBD
