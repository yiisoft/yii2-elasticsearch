# Installation

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

To use this extension, you need to configure the [[yii\elasticsearch\Connection|Connection]] class in your application configuration:

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
            // 'autodetectCluster' => false,
            'dslVersion' => 7, // default is 5
        ],
    ],
];
```

The connection needs to be configured with at least one node. The default behavior is cluster autodetection.
The extension makes a `GET /_nodes` request to the first node in the list, and gets the addresses of all the
nodes in the cluster. An active node is then randomly selected from the updated node list.

This behavior can be disabled by setting [[yii\elasticsearch\Connection::$autodetectCluster|$autodetectCluster]]
to `false`. In that case an active node will be randomly selected from the nodes given in the configuration.

> For cluster autodetection to work properly, the `GET /_nodes` request to the nodes specified in the
> configuration must return the `http_address` field for each node. This is returned by vanilla Elasticsearch instances
> by default, but has been reported to not be available in environments like AWS. In that case you need to disable
> cluster detection and specify hosts manually.
>
> It may also be useful to disable cluster autodetection for performance reasons. If a cluster has a single
> dedicated [coordinating-only node](https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-node.html#coordinating-only-node),
> it makes sense to direct all requests to that node. Is a cluster contains only a few nodes and their addresses
> are known, it may be useful to specify them explicitly.

You should set the version of the domain-specific language the extension will use to communicate with the server.
The value corresponds to the version of the Elasticsearch server.
For 5.x branch set [[yii\elasticsearch\Connection::$dslVersion|$dslVersion]] to `5`, for 6.x branch to `6`,
for 7.x branch to `7`. Default is `5`.
