<p align="center">
    <a href="https://www.elastic.co/products/elasticsearch" target="_blank" rel="external">
        <img src="https://images.contentstack.io/v3/assets/bltefdd0b53724fa2ce/blt280217a63b82a734/5bbdaacf63ed239936a7dd56/elastic-logo.svg" height="80px">
    </a>
    <h1 align="center">Elasticsearch Query and ActiveRecord for Yii 2</h1>
    <br>
</p>

This extension provides the [Elasticsearch](https://www.elastic.co/products/elasticsearch) integration for the [Yii framework 2.0](https://www.yiiframework.com).
It includes basic querying/search support and also implements the `ActiveRecord` pattern that allows you to store active
records in Elasticsearch.

For license information check the [LICENSE](LICENSE.md)-file.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii2-elasticsearch/v/stable.png)](https://packagist.org/packages/yiisoft/yii2-elasticsearch)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii2-elasticsearch/downloads.png)](https://packagist.org/packages/yiisoft/yii2-elasticsearch)
[![Build Status](https://travis-ci.com/yiisoft/yii2-elasticsearch.svg?branch=master)](https://travis-ci.com/yiisoft/yii2-elasticsearch)
[![codecov](https://codecov.io/gh/yiisoft/yii2-elasticsearch/graph/badge.svg?token=oi71bPc1SU)](https://codecov.io/gh/yiisoft/yii2-elasticsearch)

Requirements
------------

- PHP 7.3 or higher.

Depending on the version of Elasticsearch you are using you need a different version of this extension.

- For Elasticsearch 1.6.0 to 1.7.6 use extension version 2.0.x
- For Elasticsearch 5.x or above use extension version 2.1.x

Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/):


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
            'dslVersion' => 7, // default is 5
        ],
    ]
];
```
