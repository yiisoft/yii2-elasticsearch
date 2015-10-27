Installation
============

## Pré-requis

Elasticsearch version 1.0 ou supérieure est requis.

## Installer via Composer

La manière conseillée pour installer cette extension est de le faire via [composer](http://getcomposer.org/download/).

Vous pouvez soit exécuter

```
php composer.phar require --prefer-dist yiisoft/yii2-elasticsearch
```

ou ajouter

```json
"yiisoft/yii2-elasticsearch": "~2.0.0"
```

à la section `require` de votre fichier composer.json.

## Configuration de l'application

Pour utiliser cette extension, vous devez déclarer la classe Connection dans la configuration de votre application :

```php
return [
    //....
    'components' => [
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'nodes' => [
                ['http_address' => '127.0.0.1:9200'],
                // configurez plus de serveurs si vous avez un cluster
            ],
        ],
    ]
];
```
