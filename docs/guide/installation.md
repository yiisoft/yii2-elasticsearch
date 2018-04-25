Installation
============

## Requirements

Elasticsearch version 1.0 or higher is required.

## Getting Composer package

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yiisoft/yii2-elasticsearch
```

or add

```json
"yiisoft/yii2-elasticsearch": "~2.0.0"
```

to the require section of your composer.json.

## Configuring application

To use this extension, you have to configure the Connection class in your application configuration:

```php
return [
    //....
    'components' => [
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'nodes' => [
                ['http_address' => '127.0.0.1:9200'],
                // configure more hosts if you have a cluster
            ],
        ],
    ]
];
```

The connection supports auto detection of the elasticsearch cluster, which is enabled by default.
You do not need to specify all cluster nodes manually, Yii will detect other cluster nodes and connect to
a randomly selected node by default. You can disable this feature by setting [[yii\elasticsearch\Connection::$autodetectCluster]]
to `false`.

Note that for cluster autodetection to work properly, the `GET /_nodes` request to the nodes
specified in the configuration must return the `http_address` field for each node.
This is returned by vanilla elasticsearch instances by default, but has been reported to not be available in environments like AWS.
In that case you need to disable cluster detection and specify hosts manually.
