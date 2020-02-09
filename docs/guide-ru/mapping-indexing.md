Сопоставление и индексация
==================

## Создание индексов и сопоставления

Так как не всегда возможно обновлять сопоставления ElasticSearch поэтапно, рекомендуется создать несколько статических методов в вашей модели, которые занимаются созданием и обновлением индекса. Вот пример того, как это можно сделать.

```php
class Book extends yii\elasticsearch\ActiveRecord
{
    //Другие атрибуты и методы класса идут здесь
    // ...

    /**
     * @return array Сопоставление для этой модели
     */
    public static function mapping()
    {
        return [
            'properties' => [
                'name'           => ['type' => 'string'],
                'author_name'    => ['type' => 'string'],
                'publisher_name' => ['type' => 'string'],
                'created_at'     => ['type' => 'long'],
                'updated_at'     => ['type' => 'long'],
                'status'         => ['type' => 'long'],
            ],
        ];
    }

    /**
     * Установка (update) для этой модели
     */
    public static function updateMapping()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->setMapping(static::index(), static::type(), static::mapping());
    }

    /**
     * Создать индекс этой модели
     */
    public static function createIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->createIndex(static::index(), [
            'settings' => [ /* ... */ ],
            'mappings' => static::mapping(),
            //'warmers' => [ /* ... */ ],
            //'aliases' => [ /* ... */ ],
            //'creation_date' => '...'
        ]);
    }

    /**
     * Удалить индекс этой модели
     */
    public static function deleteIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->deleteIndex(static::index(), static::type());
    }
}
```

Чтобы создать индекс с соответствующими сопоставлениями, вызовите `Book::createIndex()`. Если вы изменили сопоставление таким образом, чтобы оно отображало обновление (например, создало новое свойство), вызовите `Book::updateMapping()`.

Однако, если вы изменили свойство (например, перешли от `string` к` date`), ElasticSearch не сможет обновить сопоставление. В этом случае вам нужно удалить свой индекс (путем вызова `Book::deleteIndex()`), создать его заново с обновленным сопоставлением (путем вызова `Book::createIndex()`) и затем повторно заполнить его данными.

## Индексация
TBD
