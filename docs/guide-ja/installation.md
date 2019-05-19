インストール
============

## 必要条件

Elasticsearch のバージョン 1.6.0 以上 1.7.6 以下が必要です。以下を `config/elasticsearch.yml` に追加しなければなりません。

```
script.disable_dynamic: false
```

## Composer パッケージを取得する

このエクステンションをインストールするのに推奨される方法は [composer](http://getcomposer.org/download/) によるものです。

```
composer require --prefer-dist yiisoft/yii2-elasticsearch
```

## アプリケーションを構成する

このエクステンションを使用するためには、アプリケーションの構成情報で `Connection` クラスを構成する必要があります。

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
        ],
    ]
];
```

この接続は elasticsearch クラスタの自動的な検出をサポートしており、自動検出はデフォルトで有効になっています。
全てのクラスタ・ノードを手作業で指定する必要はありません。
Yii は、デフォルトで他のクラスタ・ノードを検出して、ランダムに選ばれたノードに接続します。
この機能は [[yii\elasticsearch\Connection::$autodetectCluster]] を `false` に設定することによって無効化することが出来ます。

クラスタの自動検出が正しく働くためには、設定情報で指定されたノードに対する `GET / _nodes` リクエストに対して、
各ノードの `http_address` フィールドが返されなければならないことに留意して下さい。
このフィールドは、デフォルトでは、素の elasticsearch インスタンスによって返される筈のものですが、AWS のような環境では取得できないことが報告されています。
そのような場合には、クラスタの自動検出を無効にして、ホストを手作業で指定しなければなりません。
