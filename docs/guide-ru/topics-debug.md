Использование Elasticsearch DebugPanel
----------------------------------

Расширение yii2 elasticsearch предоставляет `DebugPanel` которая может быть интегрирована с модулем `yii debug` и показывает выполненные запросы elasticsearch. Оно также позволяет запускать эти запросы и просматривать результаты.

Добавьте следующий код в конфигурацию приложения, чтобы включить его (если у вас уже включен модуль отладки, достаточно просто добавить конфигурацию панелей):

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
