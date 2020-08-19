# Query を使う

[[yii\elasticsearch\Query]] クラスは一般的にその [[yii\db\Query|親の Query クラス]] と互換性があります。
親の Query クラスについては [ガイド](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-query-builder.md) に詳述されていますので参照して下さい。

以下では相違点を概説します。

- Elasticsearch は SQL をサポートしないため、クエリの API は `join()`、`groupBy()`、`having()`、および `union()` をサポートしません。
  ソート、`limit()`、`offset()`、`limit()`、および `where()` は（一定の制限はありますが）すべてサポートされます。

- [[yii\elasticsearch\Query::from()|from()]] はテーブルを選択するのではなく、クエリの対象となる
  [index](https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index)
  と [type](https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) を選択します。

- `select()` は [[yii\elasticsearch\Query::storedFields()|storedFields()]] に置き換えられています。
  SQL におけるカラムのように、ドキュメントから取得するフィールドを定義します。

- Elasticsearch はデータベースであると同時に検索エンジンでもあるため、SQL には無いクエリ及び集計のメカニズムが追加でサポートされています。
  クエリの構築方法について [Query DSL](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html) を参照して下さい。


## クエリを実行する

[[yii\elasticsearch\Query]] クラスがクエリを実行するための通常のメソッドを提供しています : [[yii\elasticsearch\Query::one()|one()]] および
[[yii\elasticsearch\Query::all()|all()]] です。これらのメソッドは検索結果のみを返します。

さらに [[yii\elasticsearch\Query::search()|search()]] メソッドは、検索結果に加えて、
Elasticsearch から取得される全てのメタデータ (集計データを含む) を返します。

当エクステンションは、非常に効率の良いスクロール・モードを完全にサポートしているため、巨大な検索結果を取得することが可能です。
詳細は [[yii\elasticsearch\Query::batch()|batch()]] および [[yii\elasticsearch\Query::each()|each()]] を参照して下さい。


## 返されるレコードの数とページネーションについての警告

`LIMIT` 節が提供されていない限り全ての結果を返すほとんどの SQL サーバとは違って、Elasticsearch はデフォルトで 10 レコードに結果を制限します。
それ以上に結果を取得したいときに [[yii\elasticsearch\Query::limit()|limit()]] を使います。
このことは [[yii\elasticsearch\ActiveRecord|ActiveRecord]] でリレーションを定義するときに特に重要になります。
レコードの上限を明示的に指定しなければならないのです。

一般的に Elasticsearch は深いページネーションを必要とするタスクにはあまり適していません。
最初の数ページの検索結果だけが重要視される検索エンジンとしての動作に最適化されているからです。
[[yii\elasticsearch\Query::limit()|limit()]] と [[yii\elasticsearch\Query::offset()|offset()]] を使って検索結果の下の方まで降りていくことは技術的には可能ですが、性能は劣化します。

実現可能な解決策の一つは、伝統的な SQL データベースのカーソルと同様な動作をするスクロール・モードを使用することでしょう。
スクロール・モードは [[yii\elasticsearch\Query::batch()|batch()]] および [[yii\elasticsearch\Query::each()|each()]] メソッドで実装されています。


## クエリにおけるエラー処理

Elasticsearch は分散型データベースです。この分散型の性質によって、ある種のリクエストは部分的に成功する、ということが生じます。

典型的な検索がどのように実行されるか考えてみて下さい。クエリはすべての関係するシャードに送信され、その結果が集められ、
処理されてユーザに返されます。全てのシャードが結果を返せる訳ではないということが有り得ます。
しかし、いくらかのデータが欠けていたとしても、結果は有用なものでしょう。

すべてのクエリに対してサーバは [追加のメタデータ](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-search.html#search-api-response-body) をいくつか返します。
その中には、どのシャードが失敗したかのデータも含まれますが、そういうメタデータは 
[[yii\elasticsearch\Query::one()|one()]] および [[yii\elasticsearch\Query::all()|all()]] のような Yii2 の標準メソッドを使う限りは失われてしまいます。
失敗したシャードが含まれていても、サーバのエラーであるとは見なされません。

シャードの統計情報を含む拡張されたデータを取得するためには、[[yii\elasticsearch\Query::search()|search()]] メソッドを使います。

多くの理由（接続の問題、文法エラー、その他）によってクエリそのものが失敗することもあります。
しかし、その場合は結果として例外が投げられます。


## バルク・リクエストにおけるエラー処理

Elasticsearch では [バルク・リクエスト](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html) によって単一の API 呼出で複数の操作を実行することができます。
これによりオーバーヘッドを減らし、インデクシングの速度を大きく向上させることが出来ます。

バルク・リクエストの各操作は個別に実行されますので、いくつかが成功し、いくつかが失敗するということが有り得ます。
いくつかの操作が失敗したからと言ってバルク・リクエスト全体が失敗したという扱いにはなりません。
リクエストを構成する操作のいずれかが失敗したかどうかを知ることが重要な場合は、[[yii\elasticsearch\BulkCommand::execute()|バルク・リクエストの結果]] をチェックする必要があります。

なお、接続の問題などで、バルク・リクエストそのものが失敗する場合もあります。しかし、その場合は結果として例外が投げられます。


## ES 7.0.0 以上でのドキュメントの数

Elasticsearch 7.0.0 以降、10,000件以上のヒットがある結果セットでは、ドキュメントの数 (`total_hits`) は
[デフォルトでは正確ではないものになりました](https://www.elastic.co/guide/en/elasticsearch/reference/current/breaking-changes-7.0.html#track-total-hits-10000-default)。
言い換えると、結果セットに 10,000件以上のドキュメントが含まれる場合は、`total_hits` は 10,000 と報告されます。そして 10,000件より少ない場合は、正確な数が報告されます。
これによってパフォーマンスが向上します。

`track_total_hits` オプションによってこの振る舞いを変更することが出来ます。これを `'true'` に設定すると、常に正確なドキュメント数が返されます。
また、整数の値を設定すると、デフォルトの 10,000件の閾値が上書きれます。

```
$query = new Query();
$query->from('customer');

// 注意! 文字列の 'true' であり、真偽値ではない
$query->addOptions(['track_total_hits' => 'true']);
```
