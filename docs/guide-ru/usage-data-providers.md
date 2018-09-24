Работа с провайдерами данных
===========================

Вы можете использовать [[\yii\data\ActiveDataProvider]] с [[\yii\elasticsearch\Query]] и [[\yii\elasticsearch\ActiveQuery]]:

```php
use yii\data\ActiveDataProvider;
use yii\elasticsearch\Query;

$query = new Query();
$query->from('yiitest', 'user');
$provider = new ActiveDataProvider([
    'query' => $query,
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```

```php
use yii\data\ActiveDataProvider;
use app\models\User;

$provider = new ActiveDataProvider([
    'query' => User::find(),
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```

Однако использование [[\yii\data\ActiveDataProvider]] с включенным разбиением на страницы неэффективно, так как для выполнения вычисления дополнительных запросов требуется выполнить лишний дополнительный запрос. Также он не сможет предоставить вам доступ к результатам агрегирования запросов. Вместо этого вы можете использовать `yii\elasticsearch\ActiveDataProvider`. Это дает возможность формировать общее количество элементов с помощью запроса 'meta' - информации и извлечения результатов агрегирования:

```php
use yii\elasticsearch\ActiveDataProvider;
use yii\elasticsearch\Query;

$query = new Query();
$query->from('yiitest', 'user')
    ->addAggregation('foo', 'terms', []);
$provider = new ActiveDataProvider([
    'query' => $query,
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
$aggregations = $provider->getAggregations();
$fooAggregation = $provider->getAggregation('foo');
```