インストール
============

## 必要条件

Elasticsearch バージョン 1.0 以降が必要です。

## Composer パッケージを取得する

このエクステンションをインストールするのに推奨される方法は [composer](http://getcomposer.org/download/) によるものです。

下記のコマンドを実行してください。

```
php composer.phar require --prefer-dist yiisoft/yii2-elasticsearch
```

または、あなたの `composer.json` ファイルの `require` セクションに、下記を追加してください。

```json
"yiisoft/yii2-elasticsearch": "~2.0.0"
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
