Elasticsearch DebugPanel を使う
-------------------------------

Yii 2 elasticsearch エクステンションは、yii のデバッグモジュールと統合可能な `DebugPanel` を提供しています。
これは、実行された elasticsearch のクエリを表示するだけでなく、クエリを実行して結果を表示することも出来ます。

`DebugPanel` を有効にするためには、下記の構成をアプリケーションの構成情報に追加してください
(デバッグモジュールを既に有効にしている場合は、パネルの構成情報を追加するだけで十分です)。

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

![elasticsearch DebugPanel](images/debug.png)
