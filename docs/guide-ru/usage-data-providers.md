# Работа с провайдерами данных

В расширении есть свой улучшенный и оптимизированный класс [[\yii\elasticsearch\ActiveDataProvider|ActiveDataProvider]].
По сравнению с базовым классом, выполнены такие улучшения:

- Общее количество записей получается из того же запроса, что и сами записи.
- Результаты агрегации доступны как свойство провайдера данных.

Хотя запросы [[\yii\elasticsearch\Query]] и [[\yii\elasticsearch\ActiveQuery]] совместимы со стандартным классом
[[\yii\data\ActiveDataProvider]], использовать его с ними нежелательно.

> ВАЖНО: Поскольку провайдер получает и модели, и их общее количество одним запросом, этот запрос
> выполняется уже после того, как наложены ограничения постраничного вывода. Из-за этого невозможно
> убедиться, что запрашиваемая страница действительно существует. По этой причине в провайдере
> автоматически сбрасывается параметр [[yii\data\Pagination::$validatePage]].


## Примеры использования

```php
use yii\elasticsearch\ActiveDataProvider;
use yii\elasticsearch\Query;

// С классом Query
$query = new Query();
$query->from('customer');

// Можно использовать и ActiveQuery
// $query = Customer::find();

$query->addAggregate(['date_histogram' => [
    'field' => 'registered_at',
    'calendar_interval' => 'month',
]]);

$query->addSuggester('customer_name', [
    'text' => 'Hans',
    'term' => [
        'field' => 'customer_name',
    ]
]);

$dataProvider = new ActiveDataProvider([
    'query' => $query,
    'pagination' => [
        'pageSize' => 10,
    ]
]);

$models = $dataProvider->getModels();
$aggregations = $dataProvider->getAggregations();
$suggestion = $dataProvider->getSuggestions();
```
