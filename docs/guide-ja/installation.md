#インストール

## 必要条件

このエクステンションは Elasticsearch のバージョン 5.0 以上 をサポートするべく設計されています。
Elasticsearch 5.x, 6.x, そして 7.x のブランチでテストされています。


## Elasticsearch を構成する

このエクステンションはその機能のいくつか(たとえば [[yii\elasticsearch\ActiveRecord::updateAllCounters()|updateAllCounters()]]
メソッド)のためにインライン・スクリプトを使用します。
このスクリプトは `painless` で書かれ、Elasticsearch によってサンドボックス内で実行されます。
普通はデフォルトで有効にされていますので、特別な構成は必要ありません。
しかし、古いバージョンの Elasticsearch (5.0など) では、この機能をサポートするためにインライン・スクリプトを有効にする必要があるかも知れません。
詳細は [Elasticsearch documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting-security.html) を参照して下さい。


## Composer パッケージを取得する

このエクステンションをインストールするのに推奨される方法は [composer](http://getcomposer.org/download/) によるものです。

```
composer require --prefer-dist yiisoft/yii2-elasticsearch
```


## アプリケーションを構成する

このエクステンションを使用するためには、アプリケーションの構成情報で [[yii\elasticsearch\Connection|Connection]] クラスを構成する必要があります。

```php
return [
    //....
    'components' => [
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'nodes' => [
                ['http_address' => '127.0.0.1:9200'],
                // クラスタを使用する場合は、さらにホストを構成する
            ],
            // ノードを自動検出したくない場合は autodetectCluster を false に設定する
            // 'autodetectCluster' => false,
            'dslVersion' => 7, // ドメイン固有言語のバージョン。デフォルト値は 5
        ],
    ]
];
```

この接続では少なくとも一つのノードを指定する必要があります。そしてクラスタの自動検出がデフォルトの振舞いとなります。
エクステンションはリスト上の最初のノードに `GET /_nodes` リクエストを発して、クラスタ内の全てのノードのアドレスを取得します。
そして、更新されたノード・リストの中からアクティブなノードがランダムに選ばれます。

この振舞いは [[yii\elasticsearch\Connection::$autodetectCluster]] を `false` に設定することによって無効化することが出来ます。
その場合はアクティブなノードは構成で指定されたノードの中からランダムに選ばれることになります。

> クラスタの自動検出が正しく働くためには、構成情報で指定されたノードに対する `GET / _nodes` リクエストが
> 各ノードの `http_address` フィールドが返されなければなりません。
> このフィールドは、デフォルトでは、素の Elasticsearch インスタンスによって返される筈のものですが、AWS のような環境では取得できないことが報告されています。
> そのような場合には、クラスタの自動検出を無効にして、ホストを手作業で指定しなければなりません。
>

> パフォーマンス上の理由からもクラスタの自動検出を無効にする方が有益であるかもしれません。
> クラスタに専用の [コーディネイトだけのノード](https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-node.html#coordinating-only-node) が一つしかない場合は、全てのリクエストをそのノードに向けるのが合理的でしょう。
> クラスタに数個のノードしかなくて、そのアドレスも判っている場合は、アドレスを明示的に指定する方が良いでしょう。

エクステンションがサーバと交信するのに使用するドメイン固有言語のバージョンをしていしなければなりません。
設定値は Elasticsearch サーバのバージョンと対応します。
5.x ブランチでは [[yii\elasticsearch\Connection::$dslVersion|$dslVersion]] を `5` に設定します。
6.x ブランチは `6`、7.x ブランチは `7` です。デフォルト値は `5` です。
