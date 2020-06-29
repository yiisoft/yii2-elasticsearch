Installation
============

## Requirements

The extension is designed to support Elasticsearch 5.0 and above. It has been tested with the latest versions of
Elasticsearch 5.x, 6.x, and 7.x branches.


## Configuring Elasticsearch

The extension uses inline scripts for some of its functionality (like the [[yii\elasticsearch\ActiveRecord::updateAllCounters()|updateAllCounters()]]
method). The script is written in `painless`, which is run by Elasticsearch in a sanboxed manner. Because it is generally
enabled by default, no special configuration is required. However, for older versions of Elasticsearch (like 5.0), you
may need to enable inline scripts to support this functionality.
See [Elasticsearch documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting-security.html)
for details.


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
            // set autodetectCluster to false if you don't want to auto detect nodes
            // (for example: you're using SLA after a special domain)
            // 'autodetectCluster' => false,
            'dslVersion' => 7, // default is 5
        ],
    ],
];
```

The connection supports auto detection of the Elasticsearch cluster, which is enabled by default.
You do not need to specify all cluster nodes manually, Yii will detect other cluster nodes and connect to
a randomly selected node by default. You can disable this feature by setting
[[yii\elasticsearch\Connection::$autodetectCluster|$autodetectCluster]] to `false`.

Note that for cluster autodetection to work properly, the `GET /_nodes` request to the nodes specified in the
configuration must return the `http_address` field for each node. This is returned by vanilla Elasticsearch instances
by default, but has been reported to not be available in environments like AWS. In that case you need to disable
cluster detection and specify hosts manually.

You should set the version of the domain-specific language the extension will use to communicate with the server.
For 5.x branch set [[yii\elasticsearch\Connection::$dslVersion|$dslVersion]] to `5`, for 6.x branch to `6`,
for 7.x branch to `7`. Default is `5`.
