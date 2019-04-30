マッピングとインデクシング
==========================

## インデックスとマッピングを生成する

ElasticSearch のマッピングを漸進的に更新することは常に可能であるとは限りません。ですから、あなたのモデルの中に、インデックスの生成と更新を扱ういくつかの静的なメソッドを作っておくというのは、良いアイデアです。どのようにすればそれが出来るかの一例を次に示します。

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

しかし、プロパティを変更した場合 (例えば、`string` から `date` に変えた場合など) は、ElasticSearch はマッピングを更新することが出来ません。この場合は、インデックスを削除し (`Book::deleteIndex()` を呼びます)、更新されたマッピングでインデックスを新規に作成し (`Book::createIndex()` を呼びます)、そして、データを投入しなければなりません。

## インデクシング
TBD
