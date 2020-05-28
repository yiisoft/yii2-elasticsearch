<?php

/**
 * This is the configuration file for the Yii2 ElasticSearch unit tests.
 * You can override configuration values by creating a `config.local.php` file
 * and manipulate the `$config` variable.
 * For example to change ElasticSearch http address of nodes your `config.local.php`
 * should contain the following:
 *
 * <?php
 * $config['elasticsearch']['nodes']['http_address'] = '192.168.1.2:9200';
 */

$config = [
    'elasticsearch' => [
        'autodetectCluster' => false,
        'nodes' => [
            ['http_address' => 'inet[/127.0.0.1:9200]'],
        ],
    ],
];

if (is_file(__DIR__ . '/config.local.php')) {
    include(__DIR__ . '/config.local.php');
}

return $config;
