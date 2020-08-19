# データ・プロバイダを扱う

このエクステンションは、機能拡張され最適化された独自の [[\yii\elasticsearch\ActiveDataProvider|ActiveDataProvider]] クラスを提供します。
機能拡張は下記を含みます:

- レコードの総数は、独立のクエリによらず、レコードそのものを取得するクエリによって同時に取得されます。
- 集合(Aggregation) データがデータプロバイダーのプロパティとして提供されます。

[[\yii\elasticsearch\Query]] と [[\yii\elasticsearch\ActiveQuery]] を [[\yii\data\ActiveDataProvider]] とともに使うことも可能ですが、
それは推奨されません。

> NOTE: このデータプロバイダーは検索結果のモデルと総数を単一の Elasticsearch クエリを使って取得します。
  すなわち、検索結果の総数はページネーションの limit が適用された後に取得されますので、要求するページが実際に存在するかどうかを確かめる方法はありません。
  このため、データプロバイダーは [[yii\data\Pagination::$validatePage]] を自動的に無効に設定します。


## 使用例

```php
use yii\elasticsearch\ActiveDataProvider;
use yii\elasticsearch\Query;

// Query を使う
$query = new Query();
$query->from('customer');

// ActiveQuery を使うことも出来る
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
