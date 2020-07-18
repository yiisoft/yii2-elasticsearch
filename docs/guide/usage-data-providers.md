# Working with data providers

The extension comes with its own enhanced and optimized [[\yii\elasticsearch\ActiveDataProvider|ActiveDataProvider]] class.
The enhancements include:

- Total record count is obtained from the same query that gets the records themselves, not in a separate query.
- Aggregation data is available as a property of the data provider.

While [[\yii\elasticsearch\Query]] and [[\yii\elasticsearch\ActiveQuery]] can be used with [[\yii\data\ActiveDataProvider]],
this is not recommended.

> NOTE: The data provider fetches result models and total count using single Elasticsearch query, so results total count will be fetched
  after pagination limit applying, which eliminates ability to verify if requested page number actually exist.
  Data provider disables [[yii\data\Pagination::$validatePage]] automatically because of this.


## Usage examples

```php
use yii\elasticsearch\ActiveDataProvider;
use yii\elasticsearch\Query;

// Using Query
$query = new Query();
$query->from('customer');

// ActiveQuery can also be used
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
