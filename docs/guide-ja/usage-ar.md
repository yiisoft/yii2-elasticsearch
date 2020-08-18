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

ます。す。扱いが通常と異なります。
というのは、プライマリ・キー (elasticsearch の用語では `_id` フィールド) が、デフォルトでは属性のうちに入らないからです。
ただし、`_id` フィールドを属性に含めるための [パス・マッピング]() を定義することは出来ます。
パス・マッピングの定義の仕方については、[elasticsearch のドキュメント](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) を参照してください。
document または record の `_id` フィールドは、[[yii\elasticsearch\ActiveRecord::getPrimaryKey()|getPrimaryKey()]]
および [[yii\elasticsearch\ActiveRecord::setPrimaryKey()|setPrimaryKey()]] を使ってアクセスすることが出来ます。
パス・マッピングが定義されている場合は、[[yii\elasticsearch\ActiveRecord::primaryKey()|primaryKey()]] メソッドを使って属性の名前を定義することが出来ます。

以下は `Customer` と呼ばれるモデルの例です。

```php
class Customer extends \yii\elasticsearch\ActiveRecord
{
    /**
     * @return array このレコードの属性のリスト
     */
    public function attributes()
    {
        // '_id' に対するパス・マッピングは 'id' フィールドに設定される
        return ['id', 'name', 'address', 'registration_date'];
    }

    /**
     * @return ActiveQuery Order レコード へのリレーションを定義 (Order は他のデータベース、例えば、redis や通常の SQLDB にあっても良い)
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => 'id'])->orderBy('id');
    }

    /**
     * `$query` を修正してアクティブ (status = 1) な顧客だけを返すスコープを定義
     */
    public static function active($query)
    {
        $query->andWhere(['status' => 1]);
    }
}
```

[[yii\elasticsearch\ActiveRecord::index()|index()]] と [[yii\elasticsearch\ActiveRecord::type()|type()]] をオーバーライドして、
このレコードが表すインデックスとタイプを定義することが出来ます。

elasticsearch のアクティブレコードの一般的な使用方法は、[ガイド](https://github.com/yiisoft/yii2/blob/master/docs/guide-ja/active-record.md)
で説明されたデータベースのアクティブレコードの場合と非常によく似ています。
以下の制限と拡張 (*!*) があることを除けば、同じインタフェイスと機能をサポートしています。

- elasticsearch は SQL をサポートしていないため、クエリの API は `join()`、`groupBy()`、`having()` および `union()` をサポートしません。
  並べ替え、リミット、オフセット、条件付き WHERE は、すべてサポートされています。
- [[yii\elasticsearch\ActiveQuery::from()|from()]] はテーブルを選択しません。
  そうではなく、クエリ対象の [インデックス](https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index) と
  [タイプ](https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) を選択します。
- `select()` は [[yii\elasticsearch\ActiveQuery::fields()|fields()]] に置き換えられています。
  基本的には同じことをするものですが、`fields` の方が elasticsearch の用語として相応しいでしょう。
  ドキュメントから取得するフィールドを定義します。
- Elasticsearch にはテーブルがありませんので、テーブルを通じての [[yii\elasticsearch\ActiveQuery::via()|via]] リレーションは定義することが出来ません。
- Elasticsearch はデータ・ストレージであると同時に検索エンジンでもありますので、当然ながら、レコードの検索に対するサポートが追加されています。
  Elasticsearch のクエリを構成するための [[yii\elasticsearch\ActiveQuery::query()|query()]]、
  [[yii\elasticsearch\ActiveQuery::filter()|filter()]] そして 
  [[yii\elasticsearch\ActiveQuery::addFacet()|addFacet()]] というメソッドがあります。
  これらがどのように働くかについて、下の使用例を見てください。
  また、`query` と `filter` の部分を構成する方法については、[クエリ DSL](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html)
  を参照してください。
- Elasticsearch のアクティブレコードから通常のアクティブレコード・クラスへのリレーションを定義することも可能です。また、その逆も可能です。

> Note: デフォルトでは、elasticsearch は、どんなクエリでも、返されるレコードの数を 10 に限定しています。
> もっと多くのレコードを取得することを期待する場合は、リレーションの定義で上限を明示的に指定しなければなりません。
> このことは、via() を使うリレーションにとっても重要です。
> なぜなら、via のレコードが 10 までに制限されている場合は、リレーションのレコードも 10 を超えることは出来ないからです。


使用例:

```php
$customer = new Customer();
$customer->primaryKey = 1; // この場合は、$customer->id = 1 と等価
$customer->attributes = ['name' => 'test'];
$customer->save();

$customer = Customer::get(1); // PK によってレコードを取得
$customers = Customer::mget([1,2,3]); // PK によって複数のレコードを取得
$customer = Customer::find()->where(['name' => 'test'])->one(); // クエリによる取得。レコードを正しく取得するためにはこのフィールドにマッピングを構成する必要があることに注意。
$customers = Customer::find()->active()->all(); // クエリによって全てを取得 (`active` スコープを使って)

// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
$result = Article::find()->query(["match" => ["title" => "yii"]])->all(); // articles whose title contains "yii"

// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html#query-dsl-match-query-fuzziness
$query = Article::find()->query([
    'match' => [
        'title' => [
            'query' => 'このクエリは、このテキストに似た記事を返します :-)',
            'operator' => 'and',
            'fuzziness' => 'AUTO'
        ]
    ]
]);

$query->all(); // 全てのドキュメントを取得
// 検索に facets を追加できる
$query->addStatisticalFacet('click_stats', ['field' => 'visit_count']);
$query->search(); // 全てのレコード、および、visit_count フィールドに関する統計 (例えば、平均、合計、最小、最大など) を取得
```

## 複雑なクエリ

どのようなクエリでも、Elasticsearch のクエリ DSL を使って作成して `ActiveRecord::query()` メソッドに渡すことが出来ます。しかし、ES のクエリ DSL は冗長さで悪名高いものです。長すぎるクエリは、すぐに管理できないものになってしまいます。
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
