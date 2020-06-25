Utilisation du DebugPanel d'Elasticsearch
-----------------------------------------

L'extension yii2 Elasticsearch extensions fournit un `DebugPanel` qui peut être intégré avec le module de débogage de yii, et qui affiche les requêtes Elasticsearch exécutées. Il vous permet aussi d'exécuter ces requêtes et d'en voir les résultats.

Ajoutez ce qui suit à la configuration de votre application pour l'activer (si vous avez déjà activé le module de débogage, il vous suffit d'ajouter juste la partie `panels`) :

```php
    // ...
    'bootstrap' => ['debug'],
    'modules' => [
        'debug' => [
            'class' => 'yii\\debug\\Module',
            'panels' => [
                'elasticsearch' => [
                    'class' => 'yii\\elasticsearch\\DebugPanel',
                ],
            ],
        ],
    ],
    // ...
```

![Elasticsearch DebugPanel](images/debug.png)
