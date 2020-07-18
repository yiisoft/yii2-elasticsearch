# Использование ActiveRecord

Класс Elasticsearch ActiveRecord очень похож на аналогичный класс ActiveRecord для работы с традиционными базами данных,
который описан в [руководстве](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md).

Большинство его отличий и ограничений связаны с особенностями реализации класса [[yii\elasticsearch\Query]].

Чтобы объявить класс Elasticsearch ActiveRecord, нужно унаследовать свой класс от [[yii\elasticsearch\ActiveRecord]]
и реализовать в нем как минимум метод [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]], в котором задать
атрибуты модели.

> ВАЖНО: Первичный ключ (`_id`) включать в список атрибутов НЕ НУЖНО.

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // Прочие атрибуты и методы класса
    // ...
    public function attributes()
    {
        return ['first_name', 'last_name', 'order_ids', 'email', 'registered_at', 'updated_at', 'status', 'is_active'];
    }
}
```

Переопределив методы [[yii\elasticsearch\ActiveRecord::index()|index()]] и [[yii\elasticsearch\ActiveRecord::type()|type()]]
можно задать индекс и тип, которые представляет модель.

> ВАЖНО: В Elasticsearch версии 7.x и выше типы игнорируются. Более подробно - в разделе [Структура данных и
> индексы](mapping-indexing.md).


## Примеры использования

```php
// Создание новой записи
$customer = new Customer();
$customer->_id = 1; // первичный ключ можно изменять только у несохраненных записей
$customer->last_name = 'Doe'; // атрибуты можно устанавливать по одному
$customer->attributes = ['first_name' => 'Jane', 'email' => 'janedoe@example.com']; // или группами
$customer->save();

// Получение записей по первичному ключу
$customer = Customer::get(1); // получить запись по ключу
$customer = Customer::findOne(1); // можно и так
$customers = Customer::mget([1,2,3]); // получить несколько записей по ключу
$customers = Customer::findAll([1, 2, 3]); // можно и так

// Поиск записей с помощью простых условий
$customer = Customer::find()->where(['first_name' => 'John', 'last_name' => 'Smith'])->one();

// Поиск записей с помощью языка запросов Elasticsearch
// (см. https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html)
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

## Первичные ключи

В отличие от традиционных БД SQL, где в качестве первичного ключа можно использовать любую колонку или группу колонок,
а также создавать таблицы без первичного ключа, в Elasticsearch первичный ключ хранится отдельно от документа. Ключ не
является полем документа и его нельзя изменить после того, как документ сохранен в индекс.

И хотя Elasticsearch автоматически создает уникальные первичные ключи для новых документов, при необходимости эти
ключи можно задавать явно. При этом следует учитывать, что ключевое поле представляет собой строку длиной до 512 байт.
Более подробно работа с первичными ключами описана в
[документации Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html).

В Elasticsearch первичный ключ называется `_id`, а в классе [[yii\elasticsearch\ActiveRecord]] для него предусмотрены
геттер и сеттер. Ни в коем случае не нужно добавлять ключевое поле в [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]]!


## Внешние ключи

В базах данных SQL в качестве первичных ключей часто используются автоинкрементные колонки целочисленного (`integer`)
типа. Когда модель Elasticsearch ссылается на такие модели в своих связях, эти целочисленные ключи становятся для
Elasticsearch внешними ключами.

И хотя эти ключи - числа, использовать для них целочисленные типы в Elasticsearch не следует. В Elasticsearch
поля численных типов (например, `integer` и `long`) оптимизированы для запросов по диапазону (`range`), а поля
типа `keyword` - для поиска конкретных значений (`term`). Поэтому для внешних ключей желательно использовать именно
поля типа `keyword`. См. [документацию Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/reference/current/keyword.html)
по этой теме.


## Определение связей

Расширение позволяет объявлять связи, в которых участвуют модели как из Elasticsearch, так и из других БД. Связи с
помощью метода [[yii\elasticsearch\ActiveQuery::via()|via()]] поддерживаются частично: нельзя объявить связь через
промежуточную таблицу, только через промежуточную модель.

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // У каждого клиента есть много заказов, а у каждого заказа - один инвойс

    public function getOrders()
    {
        // Через эту связь можно получить до 100 самых последних заказов клиента
        return $this->hasMany(Order::className(), ['customer_id' => '_id'])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->limit(100); // переопределяем лимит по умолчанию (10)
    }

    public function getInvoices()
    {
        // Эта связь через промежуточную модель выполняет запрос, получающий
        // эти промежуточные модели. В связи тоже нужно установить лимит,
        // и нет смысла делать его не таким, как в промежуточной модели
        return $this->hasMany(Invoice::className(), ['_id' => 'order_id'])
                    ->via('orders')->limit(100);
    }
}
```

> **ВАЖНО:** В Elasticsearch все запросы возвращают по умолчанию только первые десять записей. Это касается и запросов,
> через которые производится выборка связанных моделей. Если ожидается, что связанных моделей будет больше десяти,
> нужно явно увеличить лимит в определении связи. Это касается и промежуточных моделей, которые используются
> в методе [[yii\elasticsearch\ActiveQuery::via()|via()]]. В таком случае лимит надо задать как в самой связи, так и в
> связи, которая является промежуточной.


## Скалярные атрибуты и атрибуты-массивы

В Elasticsearch в любое поле документа [можно поместить несколько значений](https://www.elastic.co/guide/en/elasticsearch/reference/current/array.html).
Например, если у клиента есть поле типа `keyword` для номера заказа, в это поле можно поместить одно, два или больше
значений. Можно сказать, что любое поле документа - это массив.

Мы стараемся, чтобы работа с расширением поменьше отличалась от стандартного [[yii\base\ActiveRecord]]. Поэтому когда
запись заполняется данными, массивы, содержащие только одно значение, заменяются этим значением. Такое поведение можно
отключить, если указать нужное поле в методе [[yii\elasticsearch\ActiveRecord::arrayAttributes()|arrayAttributes()]].

```php
public function arrayAttributes()
{
    return ['order_ids'];
}
```

Если объявить атрибут таким образом, то при выборке из базы данных значение `$customer->order_ids` всегда будет массивом,
даже если в нем всего одно значение, например, `['AB-32162']`.


## Организация сложных запросов

В метод [[yii\elasticsearch\Query::query()|query()]] можно передать любой запрос, который написан на языке запросов
Elasticsearch. Для этого языка характерна многословность, а в объемные запросы трудно вносить дополнения и изменения.

Для решения этой задачи в классах ActiveRecord для SQL применяются методы, которые модифицируют запрос и вызываются по
цепочке. С Elasticsearch такой подход не работает, поэтому желательно создавать статические методы, которые возвращают
отдельные элементы запроса, а затем объединять их в более сложные запросы.

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

Теперь составим запрос, используя эти вспомогательные методы.

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

## Агрегации

[Механизм агрегаций](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html)
выдает обобщенные сведения о результатах поискового запроса. В его основе лежат "кирпичики" - агрегации,
совмещая которые можно получать более сложную и подробную статистику.

В качестве примера соберем сведения о регистрации клиентов по месяцам.

```php
$searchResult = Customer::find()->addAggregate('customers_by_date', [
    'date_histogram' => [
        'field' => 'registered_at',
        'calendar_interval' => 'month',
    ],
])->limit(0)->search();

$customersByDate = ArrayHelper::map($searchResult['aggregations']['customers_by_date']['buckets'], 'key_as_string', 'doc_count');
```

Следует отметить, что в этом примере используется метод [[yii\elasticsearch\ActiveQuery::search()|search()]], а не
привычные [[yii\elasticsearch\ActiveQuery::one()|one()]] или [[yii\elasticsearch\ActiveQuery::all()|all()]].
Метод `search()` возвращает не только найденные модели, но и метаданные запроса: статистику шардов, агрегации, и т.д.
Для получения обобщенной статистики часто сами результаты запроса не важны. Поэтому мы указываем серверу не возвращать
найденные документы ([[yii\elasticsearch\ActiveQuery::limit()|limit(0)]]), а только метаданные.

После небольшой обработки, массив `$customersByDate` содержит данные такой структуры:
```php
[
    '2020-01-01' => 5,
    '2020-02-01' => 3,
    '2020-03-01' => 17,
]
```

## Подсказки (suggesters)

Иногда нужно подсказать пользователю поисковые запросы, которые похожи на то, что он уже запрашивал. При этом также
важно, чтобы этим подсказкам в индексе действительно соответствовали какие-то документы.

Например, можно предложить разные варианты написания имен. Как это сделать, показано в следующем примере, а дополнительные
сведения - в [документации Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters.html).

```php
$searchResult = Customer::find()->limit(0)
->addSuggester('customer_name', [
    'text' => 'Hans',
    'term' => [
        'field' => 'name',
    ]
])->search();

// Ограничим запрос с помощью limit(0), чтобы не возвращать найденные документы,
// а только метаданные (подсказки)

$suggestions = ArrayHelper::map($searchResult["suggest"]["customer_name"], 'text', 'options');
$names = ArrayHelper::getColumn($suggestions['Hans'], 'text');
// $names == ['Hanns', 'Hannes', 'Hanse', 'Hansi']
```


## Неожиданное поведения атрибутов типа "объект"

Расширение сохраняет документы в индекс с помощью вызова `_update`. Этот вызов предназначен для частичного обновления
документов. Поэтому для всех атрибутов, у которых тип соответствующего поля в Elasticsearch - `object`, значение
будет объединено с уже имеющимся в документе значением.

Рассмотрим это на примере:

```php
$customer = new Customer();
$customer->my_attribute = ['foo' => 'v1', 'bar' => 'v2'];
$customer->save();
// сейчас значение my_attribute в Elasticsearch - {"foo": "v1", "bar": "v2"}

$customer->my_attribute = ['foo' => 'v3', 'bar' => 'v4'];
$customer->save();
// сейчас значение my_attribute в Elasticsearch - {"foo": "v3", "bar": "v4"}

$customer->my_attribute = ['baz' => 'v5'];
$customer->save();
// а сейчас значение my_attribute в Elasticsearch - {"foo": "v3", "bar": "v4", "baz": "v5"},
// а $customer->my_attribute все еще равно ['baz' => 'v5']
```

Так как такое поведение применяется только для объектов, проблему можно решить, обернув объект в массив. Поскольку для
сервера нет разницы между скалярным значением и массивом, содержащим только это значение, никакой другой код изменять
не нужно.

```php
$customer->my_attribute = [['new' => 'value']]; // note the double brackets
$customer->save();
// теперь значение my_attribute в Elasticsearch - {"new": "value"}
$customer->my_attribute = $customer->my_attribute[0]; // чтобы значение в модели соответствовало значению в БД
```

Более подробно проблема обсуждается здесь:
https://discuss.elastic.co/t/updating-an-object-field/110735
