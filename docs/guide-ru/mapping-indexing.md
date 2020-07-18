# Структура данных и индексы

## Сравнение с SQL

[В документации Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/reference/current/_mapping_concepts_across_sql_and_elasticsearch.html) подробно описаны ключевые понятия Elasticsearch и баз данных SQL, и как они соответствуют друг другу. Рассмотрим основное.

Кластер Elasticsearch состоит из отдельных серверов - узлов. Клиент отправляет запросы к одному из них. Узел передает
запрос остальным узлам кластера, собирает результаты и выдает ответ клиенту. Таким образом, кластер или представляющий
его узел примерно соответствуют базе данных SQL.

Данные в Elasticsearch хранятся в индексах. Индекс соответствует таблице SQL.

Индекс состоит из документов. Документ подобен строке в таблице SQL. В этом расширении [[yii\elasticsearch\ActiveRecord|ActiveRecord]]
представляет один документ в индексе. Операция сохранения документа в индекс называется "индексирование".

Схема или структура документа определяется так называемым маппингом. Маппинг задает поля документа, которые
соответствуют колонкам в таблице SQL. Первичный ключ в Elasticsearch - это специальное системное поле, которое нельзя
ни удалить, ни переименовать. Другие поля настраиваются разработчиком.


## Задание структуры полей

Хотя Elasticsearch и создает новые поля на лету во время индексирования документов, структуру полей желательно
объявить заранее.

Объявленное поле, как правило, нельзя изменить. Например, если текстовое поле настроено на работу с анализатором
английского языка, переключить его на другой язык можно только переиндексировав все без исключения документы в индексе.

Отдельные изменения структуры все же допускаются. Подробнее - [в документации](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html#updating-field-mappings).


## Типы документов

Изначально Elasticsearch разрабатывался как хранилище разнородных документов. Чтобы хранить документы различной
структуры в одном индексе, было введено понятие "тип". Тем не менее, этот подход себя не зарекомендовал, а с версии
[Elasticsearch 7.x](https://www.elastic.co/guide/en/elasticsearch/reference/current/removal-of-types.html) типы
уже не поддерживаются.

В настоящее время рекомендуется в каждом индексе объявлять только один тип. Если расширение настроено на работу с
Elasticsearch 7 и выше, свойство [[yii\elasticsearch\ActiveRecord::type()|type()]] игнорируется, а в запросах тип
неявно заменяется на `_doc`.


## Создание вспомогательных методов

Мы рекомендуем объявить в моделях [[yii\elasticsearch\ActiveRecord|ActiveRecord]] несколько вспомогательных методов
для работы с индексами. Ниже показана возможная реализация таких методов.

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // Другие атрибуты и методы класса
    // ...

    /**
     * @return array Маппинг этой модели
     */
    public static function mapping()
    {
        return [
            // Типы полей: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#field-datatypes
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
     * Создание или обновление маппинга модели
     */
    public static function updateMapping()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->setMapping(static::index(), static::type(), static::mapping());
    }

    /**
     * Создание индекса модели
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
     * Удаление индекса модели
     */
    public static function deleteIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->deleteIndex(static::index(), static::type());
    }
}
```

Для создания индекса с маппингом вызывается метод `Customer::createIndex()`. Если маппинг изменился, но при этом
сервер позволит обновить его на лету (например, если добавлено новое поле), вызывается метод `Customer::updateMapping()`.

Если изменение значительное (например, переход от типа `string` к `date`), сервер не сможет обновить маппинг
уже существующего индекса. В таком случае придется удалить индекс с помощью `Customer::deleteIndex()`, затем создать
индекс с новым маппингом с помощью `Customer::createIndex()`), а после этого заново наполнить индекс данными.
