アクティブレコードを使う
========================

Yii のアクティブレコードの使用方法に関する一般的な情報については、[ガイド](https://github.com/yiisoft/yii2/blob/master/docs/guide-ja/db-active-record.md) を参照してください。

Elasticsearch のアクティブレコードを定義するためには、あなたのレコードクラスを [[yii\elasticsearch\ActiveRecord]] から拡張して、最低限、レコードの属性を定義するための [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]] メソッドを実装する必要があります。
Elasticsearch ではプライマリキーの扱いが通常と異なります。
というのは、プライマリキー (elasticsearch の用語では `_id` フィールド) が、デフォルトでは属性のうちに入らないからです。
ただし、`_id` フィールドを属性に含めるための [パスマッピング](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) を定義することは出来ます。
パスマッピングの定義の仕方については、[elasticsearch のドキュメント](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) を参照してください。
document または record の `_id` フィールドは、[[yii\elasticsearch\ActiveRecord::getPrimaryKey()|getPrimaryKey()]] および [[yii\elasticsearch\ActiveRecord::setPrimaryKey()|setPrimaryKey()]] を使ってアクセスすることが出来ます。
パスマッピングが定義されている場合は、[[yii\elasticsearch\ActiveRecord::primaryKey()|primaryKey()]] メソッドを使って属性の名前を定義することが出来ます。

以下は `Customer` と呼ばれるモデルの例です。

```php
class Customer extends \yii\elasticsearch\ActiveRecord
{
    /**
     * @return array このレコードの属性のリスト
     */
    public function attributes()
    {
        // '_id' に対するパスマッピングis setup to field 'id'
        return ['id', 'name', 'address', 'registration_date'];
    }

    /**
     * @return ActiveQuery Order レコード へのリレーションを定義
     * (Order は他のデータベース、例えば、redis や通常の SQLDB にあっても良い)
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

[[yii\elasticsearch\ActiveRecord::index()|index()]] と [[yii\elasticsearch\ActiveRecord::type()|type()]] をオーバーライドして、このレコードが表すインデックスとタイプを定義することが出来ます。

elasticsearch のアクティブレコードの一般的な使用方法は、[ガイド](https://github.com/yiisoft/yii2/blob/master/docs/guide-ja/active-record.md) で説明されたデータベースのアクティブレコードの場合と非常によく似ています。
以下の制限と拡張 (*!*) があることを除けば、同じインターフェイスと機能をサポートしています。

- elasticsearch は SQL をサポートしていないため、クエリの API は `join()`、`groupBy()`、`having()` および `union()` をサポートしません。
  並べ替え、リミット、オフセット、条件付き WHERE は、すべてサポートされています。
- [[yii\elasticsearch\ActiveQuery::from()|from()]] はテーブルを選択しません。
  そうではなく、クエリ対象の [インデックス](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index) と [タイプ](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) を選択します。
- `select()` は [[yii\elasticsearch\ActiveQuery::fields()|fields()]] に置き換えられています。
  基本的には同じことをするものですが、`fields` の方が elasticsearch の用語として相応しいでしょう。
  ドキュメントから取得するフィールドを定義します。
- Elasticsearch にはテーブルがありませんので、テーブルを通じての [[yii\elasticsearch\ActiveQuery::via()|via]] リレーションは定義することが出来ません。
- Elasticsearch はデータストレージであると同時に検索エンジンでもありますので、当然ながら、レコードの検索に対するサポートが追加されています。
  Elasticsearch のクエリを構成するための [[yii\elasticsearch\ActiveQuery::query()|query()]]、[[yii\elasticsearch\ActiveQuery::filter()|filter()]] そして [[yii\elasticsearch\ActiveQuery::addFacet()|addFacet()]] というメソッドがあります。
  これらがどのように働くかについて、下の使用例を見てください。
  また、`query` と `filter` の部分を構成する方法については、[Query DSL](http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html) を参照してください。
- Elasticsearch のアクティブレコードから通常のアクティブレコードクラスへのリレーションを定義することも可能です。また、その逆も可能です。

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

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
$result = Article::find()->query(["match" => ["title" => "yii"]])->all(); // articles whose title contains "yii"

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
$query = Article::find()->query([
    "fuzzy_like_this" => [
        "fields" => ["title", "description"],
        "like_text" => "このクエリは、このテキストに似た記事を返します :-)",
        "max_query_terms" => 12
    ]
]);

$query->all(); // 全てのドキュメントを取得
// 検索に facets を追加できる
$query->addStatisticalFacet('click_stats', ['field' => 'visit_count']);
$query->search(); // 全てのレコード、および、visit_count フィールドに関する統計 (例えば、平均、合計、最小、最大など) を取得
```

そして、まだ、いろいろと沢山あります。
"it's endless what you can build"[?](https://www.elastic.co/)
