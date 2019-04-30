データ・プロバイダを扱う
========================

[[\yii\elasticsearch\Query]] や [[\yii\elasticsearch\ActiveQuery]] を [[\yii\data\ActiveDataProvider]] で使用することが出来ます。

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

ただし、ページネーションを有効にして [[\yii\data\ActiveDataProvider]] を使用するのは非効率的です。
何故なら、ページネーションのためには、総アイテム数を取得するための余計なクエリが追加で必要になるからです。
また、クエリの集合 (Aggregations) の結果にアクセスすることも出来ません。代りに、 `yii\elasticsearch\ActiveDataProvider` を使うことが出来ます。
こちらであれば、'meta' 情報のクエリを使って総アイテム数を準備したり、集合の結果を取得したりすることが出来ます。

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
