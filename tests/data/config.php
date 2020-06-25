<?php

/**
 * This is the configuration file for the Yii2 Elasticsearch unit tests.
 * You can override configuration values by creating a `config.local.php` file
 * and manipulate the `$config` variable.
 * For example to change Elasticsearch http address of nodes your `config.local.php`
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

$esVersion = getenv('ES_VERSION');
if (preg_match('/^\d+/', $esVersion, $matches)) {
    $config['elasticsearch']['dslVersion'] = $matches[0];
}

if (is_file(__DIR__ . '/config.local.php')) {
    include(__DIR__ . '/config.local.php');
}

return $config;
