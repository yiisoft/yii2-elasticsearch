Использование ActiveRecord
======================

Для получения общей информации о том, как использовать yii ActiveRecord, пожалуйста, обратитесь к [руководству](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md).

Для определения класса Elasticsearch ActiveRecord ваш класс должен быть расширен от [[yii\elasticsearch\ActiveRecord]] и реализовывать, по крайней мере, метод [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]] для определения атрибутов записи.

Обработка первичных ключей в Elasticsearch различна, поскольку первичный ключ (поле `_id` в терминах Elasticsearch) по умолчанию не является частью атрибутов. Однако можно определить [сопоставление пути](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) для поля `_id` чтобы стать частью атрибута.

Смотри [документацию Elasticsearch](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) как определить это. Поле `_id` документа/записи можно получить используя [[yii\elasticsearch\ActiveRecord::getPrimaryKey()|getPrimaryKey()]] и [[yii\elasticsearch\ActiveRecord::setPrimaryKey()|setPrimaryKey()]]. Когда определено сопоставление пути, имя атрибута может быть определено с помощью метода [[yii\elasticsearch\ActiveRecord::primaryKey()|primaryKey()]].

Ниже приведен пример модели `Customer`:

```php
class Customer extends \yii\elasticsearch\ActiveRecord
{
    /**
     * @return array список атрибутов для этой записи
     */
    public function attributes()
    {
        // path mapping for '_id' is setup to field 'id'
        return ['id', 'name', 'address', 'registration_date'];
    }

    /**
     * @return ActiveQuery определение связи записи Order (может быть в другой базе данных, например redis или sql)
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => 'id'])->orderBy('id');
    }

    /**
     * Определяет область, изменяющая `$query`, которая вернет только активных (status = 1) клиентов
     */
    public static function active($query)
    {
        $query->andWhere(['status' => 1]);
    }
}
```

Вы можете переопределить [[yii\elasticsearch\ActiveRecord::index()|index()]] и [[yii\elasticsearch\ActiveRecord::type()|type()]] чтобы определить индекс и тип этой записи.

Общее использование, `Elasticsearch ActiveRecord` очень похоже на `database ActiveRecord`, описано в [руководстве](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md).
Он поддерживает тот же интерфейс и функции, за исключением следующих ограничений и дополнений(*!*):

- Посколку Elasticsearch не поддерживает SQL, API запросов не поддреживает `join()`, `groupBy()`, `having()` и `union()`.
  Сортировка, `limit`, `offset` и условия поддерживаются.
- [[yii\elasticsearch\ActiveQuery::from()|from()]] не выбирает таблицы, но [индекс](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index) и [тип](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) запрашивают.
- `select()` был заменен на [[yii\elasticsearch\ActiveQuery::fields()|fields()]] который, в основном, делает тоже самое, но `fields` является более подходящим в терминологии Elasticsearch. Он определяет поля для извлечения из документа.
- [[yii\elasticsearch\ActiveQuery::via()|via]] - отношения не могут быть определены через таблицу, так как в Elasticsearch нет таблиц. Вы можете определять отношения только через другие записи.
- Поскольку Elasticsearch - это не только хранилище данных, но и поисковая система, была добавлена поддержка для поиска ваших записей. Есть [[yii\elasticsearch\ActiveQuery::query()|query()]], [[yii\elasticsearch\ActiveQuery::filter()|filter()]] и [[yii\elasticsearch\ActiveQuery::addFacet()|addFacet()]] методы, которые позволяют составить запрос в Elasticsearch. См. пример использования ниже, как они работают, и проверьте [Query DSL](http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html) о том как составлять части `query` и `filter`.
- Также можно определить отношения от Elasticsearch ActiveRecords до обычных классов ActiveRecord и наоборот.

> **NOTE:** Elasticsearch ограничивает количество записей, возвращаемых любым запросом, до 10 записей по умолчанию.
> Если вы ожидаете получить больше записей, вы должны явно указать ограничение в запросе а также определить отношения.
> Это также важно для отношений, которые используют `via()`, так что если записи `via` ограничены 10-ю, записей отношения также может быть не более 10-и.

Пример использования:

```php
$customer = new Customer();
$customer->primaryKey = 1; // в этом случае эквивалентно $customer->id = 1;
$customer->attributes = ['name' => 'test'];
$customer->save();

$customer = Customer::get(1); // получить запись по первичному ключу
$customers = Customer::mget([1,2,3]); // получитть множественные записи по первичному ключу
$customer = Customer::find()->where(['name' => 'test'])->one(); // найти по запросу. Обратите внимание, вам необходимо настроить сопоставление для этого поля, чтобы правильно найти запись
$customers = Customer::find()->active()->all(); // найти все по запросу (используя область видимости `active`)

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
$result = Article::find()->query(["match" => ["title" => "yii"]])->all(); // статьи название которых содержит "yii"

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
$query = Article::find()->query([
    "fuzzy_like_this" => [
        "fields" => ["title", "description"],
        "like_text" => "This query will return articles that are similar to this text :-)",
        "max_query_terms" => 12
    ]
]);

$query->all(); // вернет все документы
// вы можете добавить фасеты к вашему поиску:
$query->addStatisticalFacet('click_stats', ['field' => 'visit_count']);
$query->search(); // вернет все записи + статистику о поле visit_count. Например: среднее, сумма, мин, макс и т.д...
```

## Комплексные запросы

Любой запрос может быть составлен с использованием запроса DSL ElasticSearch и передан методу `ActiveRecord::query()`. Однако DS-запрос известен своей многословностью, и эти запросы большего размера вскоре становятся неуправляемыми.
Есть способ сделать запросы более удобными. Начните с определения класса запросов так же, как это делается для SQL ActiveRecord.

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
        return ['range' => ['registration_date' => [
            'gte' => $dateFrom,
            'lte' => $dateTo,
        ]]];
    }
}

```

Теперь вы можете использовать эти компоненты запроса для сборки результирующего запроса и/или фильтра.

```php
$customers = Customer::find()->filter([
    CustomerQuery::registrationDateRange('2016-01-01', '2016-01-20'),
])->query([
    'bool' => [
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

## Агрегирование

[Фреймворк агрегирования](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html) 
помогает предоставлять агрегированные данные на основе поискового запроса. Он основан на простых строительных блоках, называемых агрегатами, которые могут быть составлены для создания сложных сводок данных.

Используя ранее определенный класс `Customer`, давайте выясним, сколько клиентов регистрировалось каждый день. Для этого мы используем агрегацию `terms`.


```php
$aggData = Customer::find()->addAggregation('customers_by_date', 'terms', [
    'field' => 'registration_date',
    'order' => ['_count' => 'desc'],
    'size' => 10, //top 10 registration dates
])->search(null, ['search_type' => 'count']);

```                    

В этом примере мы специально запрашиваем только результаты агрегации. Следующий код обрабатывает данные.

```php
$customersByDate = ArrayHelper::map($aggData['aggregations']['customers_by_date']['buckets'], 'key', 'doc_count');
```

Теперь `$customersByDate` содержит 10 дат, которые соответствуют наибольшему числу зарегистрированных пользователей.
