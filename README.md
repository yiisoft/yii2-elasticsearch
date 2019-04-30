<p align="center">
    <a href="https://www.elastic.co/products/elasticsearch" target="_blank" rel="external">
        <img src="https://static-www.elastic.co/assets/blt45b0886c90beceee/logo-elastic.svg" height="80px">
    </a>
    <h1 align="center">Elasticsearch Query and ActiveRecord for Yii 2</h1>
    <br>
</p>

This extension provides the [elasticsearch](https://www.elastic.co/products/elasticsearch) integration for the [Yii framework 2.0](http://www.yiiframework.com).
It includes basic querying/search support and also implements the `ActiveRecord` pattern that allows you to store active
records in elasticsearch.

For license information check the [LICENSE](LICENSE.md)-file.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii2-elasticsearch/v/stable.png)](https://packagist.org/packages/yiisoft/yii2-elasticsearch)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii2-elasticsearch/downloads.png)](https://packagist.org/packages/yiisoft/yii2-elasticsearch)
[![Build Status](https://travis-ci.org/yiisoft/yii2-elasticsearch.svg?branch=master)](https://travis-ci.org/yiisoft/yii2-elasticsearch)

Requirements
------------

Dependent on the version of elasticsearch you are using you need a different version of this extension.

- Extension version 2.0.x works with elasticsearch version 1.6.0 to 1.7.6.
- Extension version 2.1.x requires at least elasticsearch version 5.0.

Note: to provide the work necessary a requests to the delete by query, in elasticsearch [since version 2.0](https://www.elastic.co/guide/en/elasticsearch/reference/1.7/docs-delete-by-query.html), you should to install the plugin [delete-by-query](https://www.elastic.co/guide/en/elasticsearch/plugins/2.3/plugins-delete-by-query.html)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/):


```
composer require --prefer-dist yiisoft/yii2-elasticsearch:"~2.1.0"
```

Configuration
-------------

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
