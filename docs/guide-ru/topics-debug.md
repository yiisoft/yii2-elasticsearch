# Использование Elasticsearch DebugPanel

В расширении Yii2 Elasticsearch имеется панель отладки (`DebugPanel`), которую можно интегрировать с
модулем `yii debug`. На панели выводятся выполненные запросы Elasticsearch. Также можно запускать
эти запросы повторно и просматривать результаты.

Добавьте следующий код в конфигурацию приложения, чтобы подключить панель. Если модуль отладки уже
включен, достаточно просто добавить конфигурацию в секцию `panels`:

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
