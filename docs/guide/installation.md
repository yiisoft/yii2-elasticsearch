Installation
============

## Requirements

Elasticsearch versions from 1.6.0 to 1.7.6. The following should be added to `config/elasticsearch.yml`:

```
script.disable_dynamic: false
```

## Getting Composer package

The preferred way to install this extension is through [composer](http://getcomposer.org/download/):

```
composer require --prefer-dist yiisoft/yii2-elasticsearch
```

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
