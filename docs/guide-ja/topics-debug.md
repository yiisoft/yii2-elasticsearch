# Elasticsearch デバッグ・パネルを使う

Yii 2 Elasticsearch エクステンションは、yii のデバッグ・モジュールと統合可能な `DebugPanel` を提供しています。
これは、実行された Elasticsearch のクエリを表示するだけでなく、
クエリを実行して結果を表示することも出来ます。

`DebugPanel` を有効にするためには、下記の構成をアプリケーションの構成情報に追加してください
(デバッグ・モジュールを既に有効にしている場合は、パネルの構成情報を追加するだけで十分です)。

```php
    // ...
    'bootstrap' => ['debug'],
    'modules' => [
        'debug' => [
            'class' => 'yii\\debug\\Module',
            'panels' => [
                'elasticsearch' => [
                    'class' => 'yii\\elasticsearch\\DebugPanel',
                ],
            ],
        ],
    ],
    // ...
```

![Elasticsearch デバッグ・パネル](images/debug.png)
