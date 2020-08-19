# アクティブレコードを使う

Elasticsearch の アクティブレコードは [ガイド](https://github.com/yiisoft/yii2/blob/master/docs/guide-ja/db-active-record.md)
で述べられているデータベースのアクティブレコードと非常によく似ています。

その制限や相違のほとんどは [[yii\elasticsearch\Query]] の実装に由来するものです。

Elasticsearch のアクティブレコードを定義するためには、あなたのレコード・クラスを [[yii\elasticsearch\ActiveRecord]] から拡張して、
最低限、レコードの属性を定義するための [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]]
メソッドを実装する必要があります。

> NOTE: プライマリ・キーの属性 (`_id`) を属性に含め**ない**ことが重要です。

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // クラスの他の属性とメソッド
    // ...
    public function attributes()
    {
        return ['first_name', 'last_name', 'order_ids', 'email', 'registered_at', 'updated_at', 'status', 'is_active'];
    }
}
```

[[yii\elasticsearch\ActiveRecord::index()|index()]] と [[yii\elasticsearch\ActiveRecord::type()|type()]]
をオーバーライドして、インデクスとこのレコードが表す型を定義することが出来ます。

> NOTE: Type は Elasticsearch 7.x 以上では無視されます。詳しくは [データのマッピングとインデクシング](mapping-indexing.md) を参照して下さい。


## 使用例

```php
// 新しいレコードを作成する
$customer = new Customer();
$customer->_id = 1; // プライマリ・キーの設定は新しいレコードに対してのみ許容される
$customer->last_name = 'Doe'; // 属性は一つ一つ設定してもよいし
$customer->attributes = ['first_name' => 'Jane', 'email' => 'janedoe@example.com']; // まとめて設定してもよい
$customer->save();

// プライマリ・キーを使ってレコードを取得する
$customer = Customer::get(1); // PK でレコードを取得
$customer = Customer::findOne(1); // これでもよい
$customers = Customer::mget([1,2,3]); // PK で複数のレコードを取得
$customers = Customer::findAll([1, 2, 3]); // これでもよい

// 単純な条件を使ってレコードを検索する
$customer = Customer::find()->where(['first_name' => 'John', 'last_name' => 'Smith'])->one();

// クエリ DSL を使ってレコードを検索する
// (https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html を参照)
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

## プライマリ・キー

伝統的な SQL データベースでは、カラムまたはカラムのセットをプライマリ・キーとして選んだり、更にはプライマリ・キーを持たないテーブルを作ったり出来ますが、
Elasticsearch ではプライマリ・キーをドキュメントの他のフィールドとは分けて保存します。
プライマリ・キーはドキュメントの構造の一部をなすものではなく、一旦ドキュメントがインデクスに保存されると変更することが出来ないものになります。

新しいドキュメントに対しては Elasticsearch がユニークなプライマリ・キーを生成することも出来ますが、新しいレコードに対して明示的にプライマリ・キーを指定することも出来ます。
プライマリ・キーの属性は文字列であり、512 バイトに制限されていることに注意して下さい。
詳細は [Elasticsearch のドキュメント](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html)
を参照して下さい。

Elasticsearch ではプライマリ・キーの名前は `_id` です。[[yii\elasticsearch\ActiveRecord]] がゲッターとセッターのメソッドを提供しているため、プロパティとしてアクセスすることが可能です。
プライマリ・キーは [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]] に追加する必要はありません。


## 外部キー

SQL データベースではオート・インクリメントの整数カラムをプライマリ・キーとして使うことがよくあります。
そういうモデルを Elasticsearch のモデルでリレーションとして扱う場合、そういう整数を外部キーとするのが効率的です。

そういうキーは技術的に数値ではあるのですが、ふつうは数値型のフィールドとしてマップすべきではありません。
Elasticsearch は数値型、例えば整数型や長整数型のフィールドを範囲クエリのために最適化します。
しかし、キーワード型フィールドの方が用語クエリおよび他の用語レベルのクエリには良いのです。
したがって、外部キーには `keyword` フィールド型を使うことが推奨されます。
キーワード型フィールドの詳細は [Elasticsearch のドキュメント](https://www.elastic.co/guide/en/elasticsearch/reference/current/keyword.html) を参照してください。


## リレーションを定義する

Elasticsearch のアクティブレコードから他の Elasticsearch または Elasticsearch でないアクティブレコードのクラスへのリレーション、またはその逆のリレーションを定義することが可能です。
しかし、[[yii\elasticsearch\ActiveQuery::via()|Via]]-リレーションをテーブルを使って定義することは出来ません。なぜなら Elasticsearch にはテーブルは無いからです。
間接的リレーションは他のリレーションを使ってのみ定義することが出来ます。

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // すべての顧客は複数の注文を有し、すべての注文はただ一つの送り状を有する

    public function getOrders()
    {
        // このリレーションは現在の顧客の最近 100 までの注文を返す
        return $this->hasMany(Order::className(), ['customer_id' => '_id'])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->limit(100); // デフォルトの limit 10 をオーバーライド
    }

    public function getInvoices()
    {
        // この間接リレーションは最初に "orders" リレーションのモデルを
        // 取得することで動作する。このクエリにも limit が必要になるが、
        // 依存するリレーションの limit と異なる limit を設定することは
        // 理に適わない。
        return $this->hasMany(Invoice::className(), ['_id' => 'order_id'])
                    ->via('orders')->limit(100);
    }
}
```

> **NOTE:** デフォルトでは、Elasticsearch は、どんなクエリでも、返されるレコードの数を 10 に限定しています。
> このことはリレーションのモデルを取得するクエリを実行するときにもあてはまります。
> もっと多くのレコードを取得することを期待する場合は、リレーションの定義で上限を明示的に指定しなければなりません。
> また、[[yii\elasticsearch\ActiveQuery::via()|via]] を使う間接リレーションの場合は、 その間接リレーション自体および仲介者となるリレーションの両方において limit を適切に設定することが重要です。


## スカラと配列の属性

Elasticsearch ドキュメントのすべてのフィールドは [複数の値を保持できます](https://www.elastic.co/guide/en/elasticsearch/reference/current/array.html)。
例えば、`Customer` のマッピングに `Order ID` のキーワード・フィールドを持たせた場合、
2個以上の `Order ID` を持つドキュメントを作成することが自動的に可能になります。
ドキュメントのすべてのフィールドは配列であると言うこともできます。

[[yii\base\ActiveRecord]] との整合性のために、レコードにデータを投入するときは、要素一つだけの配列は要素の値に置き換えられます。
しかし、この振る舞いは [[yii\elasticsearch\ActiveRecord::arrayAttributes()|arrayAttributes()]] を定義することでオーバーライドすることが可能です。

```php
public function arrayAttributes()
{
    return ['order_ids'];
}
```

このようにすると、データベースから取得した時に `$customer->order_ids` は、要素が一つしか無くても、配列になります。
例えば、`['AB-32162']` です。


## 複雑なクエリを組織化する

どのようなクエリでも、Elasticsearch のクエリ DSL を使って作成して `ActiveRecord::query()` メソッドに渡すことが出来ます。
しかし、ES のクエリ DSL は冗長さで悪名高いものです。長すぎるクエリは、すぐに管理できないものになってしまいます。

クエリをもっと保守しやすくする方法があります。SQL ベースの `ActiveRecord` のために定義されているようなクエリクラスを定義することから始めましょう。

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

こうすれば、これらのクエリ・コンポーネントを、結果となるクエリやフィルタを組み上げるために使用することが出来ます。

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

## 集合 (Aggregations)

[集合フレームワーク](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html) が、検索クエリに基づいた集合データを提供するのを助けてくれます。これは集合 (aggregation) と呼ばれる単純な構成要素に基づくもので、複雑なデータの要約を構築するために作成することが出来るものです。

以前に定義された `Customer` クラスを使って、毎日何人の顧客が登録されているかを検索しましょう。そうするために `terms` 集合を使います。


```php
$aggData = Customer::find()->addAggregate('customers_by_date', [
    'terms' => [
        'field' => 'registration_date',
        'order' => ['_count' => 'desc'],
        'size' => 10, // 登録日の上位 10
    ],
])->search(null, ['search_type' => 'count']);

```                    

この例では、集合の結果だけを特にリクエストしています。データを更に処理するために次のコードを使います。

```php
$customersByDate = ArrayHelper::map($aggData['aggregations']['customers_by_date']['buckets'], 'key', 'doc_count');
```

これで `$customersByDate` に、ユーザー登録数の最も多い日付け上位 10 個が入ります。


## オブジェクトにマップされた属性の異常な振る舞いについて

このエクステンションは `_update` エンドポイントを使ってレコードを更新します。このエンドポイントはドキュメントの部分更新をするように設計されているため、Elasticsearch で "オブジェクト" マップ型を持つ全ての属性は既存のデータとマージされます。例示しましょう。

```
$customer = new Customer();
$customer->my_attribute = ['foo' => 'v1', 'bar' => 'v2'];
$customer->save();
// この時点で Elasticsearch における my_attribute の値は {"foo": "v1", "bar": "v2"}

$customer->my_attribute = ['foo' => 'v3', 'bar' => 'v4'];
$customer->save();
// Elasticsearch における my_attribute の値は {"foo": "v3", "bar": "v4"} となる

$customer->my_attribute = ['baz' => 'v5'];
$customer->save();
// Elasticsearch における my_attribute の値は {"foo": "v3", "bar": "v4", "baz": "v5"} となる
// しかし $customer->my_attribute は ['baz' => 'v5'] に等しいままである
```

このロジックはオブジェクトに対してのみ適用されるので、オブジェクトを単一要素の配列に包むことが解決策になります。Elasticsearch にとっては単一要素の配列は要素自体と同じものであるため、それ以外のコードを修正する必要はありません。

```
$customer->my_attribute = [['new' => 'value']]; // 二重括弧に注意
$customer->save();
// Elasticsearch における my_attribute の値は {"new": "value"} になる
$customer->my_attribute = $customer->my_attribute[0]; // 一貫性のためにこうしてもよい
```

詳細については次の議論を参照して下さい。
https://discuss.elastic.co/t/updating-an-object-field/110735
