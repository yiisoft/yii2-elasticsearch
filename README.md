Elasticsearch Query and ActiveRecord for Yii 2
==============================================

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

elasticsearch version 1.0 or higher is required.

Installation
------------

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
