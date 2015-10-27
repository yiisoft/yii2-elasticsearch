Utilisation des ActiveRecord
============================

Pour des informations générales sur l'utilisation d'ActiveRecord avec yii, référez-vous à cette section du [guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md).

Afin de définir une classe ActiveRecord elasticsearch, votre classe doit étendre [[yii\elasticsearch\ActiveRecord]] et implémenter au moins la méthode [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]] pour définir les attributes de votre enregistrement.
La gestion des clefs primaires est différente dans elasticsearch, étant donné que la clef primaire (le champ `_id` dans elasticsearch) ne fait pas partie des attributs par défaut. Il est cependant possible de définir un [mapping de path](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html)
pour que le champ `_id` fasse partie des attributs.
Référez-vous à la [documentation elasticsearch](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) pour voir comment le définir.
Le champ `_id` d'un document/enregistrement peut être accédé en utilisant [[yii\elasticsearch\ActiveRecord::getPrimaryKey()|getPrimaryKey()]] et
[[yii\elasticsearch\ActiveRecord::setPrimaryKey()|setPrimaryKey()]].
Lorsque le mapping de path est défini, le nom de l'attribut peut être défini en utilisant la méthode [[yii\elasticsearch\ActiveRecord::primaryKey()|primaryKey()]].

Voici un modèle d'exemple nommé `Customer`:

```php
class Customer extends \yii\elasticsearch\ActiveRecord
{
    /**
     * @return array the list of attributes for this record
     */
    public function attributes()
    {
        // le mapping de '_id' est configuré pour le champ 'id'
        return ['id', 'name', 'address', 'registration_date'];
    }

    /**
     * @return ActiveQuery defines a relation to the Order record (can be in other database, e.g. redis or sql)
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => 'id'])->orderBy('id');
    }

    /**
     * Defines a scope that modifies the `$query` to return only active(status = 1) customers
     */
    public static function active($query)
    {
        $query->andWhere(['status' => 1]);
    }
}
```

Vous pouvez surcharger [[yii\elasticsearch\ActiveRecord::index()|index()]] et [[yii\elasticsearch\ActiveRecord::type()|type()]] pour définir l'index et type que cet enregistrement représente.

L'utilisation des ActiveRecord elasticsearch est très similaire à celle d'ActiveRecord pour les bases de données, et qui est décrite dans le 
[guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md).
Elle supporte la même interface et les mêmes fonctionnalités, exceptions faite des limitations et additions suivantes (*!*) :

- Etant donné que elasticsearch ne supporte pas SQL, l'API de requêtes ne supporte pas `join()`, `groupBy()`, `having()` and `union()`.
  Les tris, limites, décallages et conditions sont tous supportés.
- [[yii\elasticsearch\ActiveQuery::from()|from()]] ne sélectionne pas les tables, mais les
  [index](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index)
  et [type](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) sur lesquels la requête sera exécutée.
- `select()` a été remplacée par [[yii\elasticsearch\ActiveQuery::fields()|fields()]] qui fait globalement la même chose, le terme `fields` étant plus orienté vers la terminologie elasticsearch.
  Cette méthode définit les champs à récupérer dans le document.
- Les relations [[yii\elasticsearch\ActiveQuery::via()|via]] ne peuvent être définies via une table étant donné qu'il n'y a pas de tables dans elasticsearch. Vous pouvez uniquement définir des relations avec les autres enregistrements.
- Etant donné qu'elasticsearch n'est pas uniquement fait pour le stockage des données, mais également pour les rechercher, le support de la recherche a été implémenté.
  Les méthodes
  [[yii\elasticsearch\ActiveQuery::query()|query()]],
  [[yii\elasticsearch\ActiveQuery::filter()|filter()]] et
  [[yii\elasticsearch\ActiveQuery::addFacet()|addFacet()]] permettent de définir une requête elasticsearch.
  Regardez l'exemple suivant pour comprendre leur fonctionnement et voir l'utilisation des [Query DSL](http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html)
  pour composer les parties `query` et `filter`.
- Il est aussi possible de définir des relations entre des ActiveRecord elasticsearch et des ActiveRecord normaux, et vice versa.

> **NOTE:** elasticsearch limite le nombre d'enregistrements retournés par n'importe quelle requête à 10 enregistrements par défaut.
> Si vous vous attendez à obtenir plus de résultats, vous devez explicitement définir leur nombre dans la requête **et également** dans la définition de la relation.
> Ceci est également important pour les relations qui utilisent via(), étant donné que si les enregistrements "via" sont limités à 10, les enregistrements liés ne pourront également pas dépasser la limite de 10.

Exemple d'utilisation :

```php
$customer = new Customer();
$customer->primaryKey = 1; // ici, équivalent à $customer->id = 1;
$customer->attributes = ['name' => 'test'];
$customer->save();

$customer = Customer::get(1); // récupère un document par pk
$customers = Customer::mget([1,2,3]); // récupère plusieurs documents par pk
$customer = Customer::find()->where(['name' => 'test'])->one(); // récupère par requête, vous devez configurer le mapping de ce champ afin que la recherche fonctionne correctement
$customers = Customer::find()->active()->all(); // récupère l'ensemble des documents en utilisant une requête (et le scope `active`)

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
$result = Article::find()->query(["match" => ["title" => "yii"]])->all(); // articles dont le titre contient "yii"

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
$query = Article::find()->query([
    "fuzzy_like_this" => [
        "fields" => ["title", "description"],
        "like_text" => "Cette requête retournera les articles similaires à ce texte :-)",
        "max_query_terms" => 12
    ]
]);

$query->all(); // retourne l'ensemble des documents
// vous pouvez ajouter des facets à votre recherche :
$query->addStatisticalFacet('click_stats', ['field' => 'visit_count']);
$query->search(); // retourne l'ensemble des documents + des statistiques à propos du champ visit_count (minimum, maximum, somme, ..)
```

Et beaucoup plus. "Ce que vous pouvez bâtir est infini"[?](https://www.elastic.co/)
