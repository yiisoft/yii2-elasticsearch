# マッピングとインデクシング

## SQL との比較

[Elasticsearch のドキュメント](https://www.elastic.co/guide/en/elasticsearch/reference/current/_mapping_concepts_across_sql_and_elasticsearch.html) では Elasticsearch と SQL の概念について広範囲にわたって解説しています。
私たちは基本に注力しましょう。

Elasticsearch クラスタは一つ以上の Elasticsearch インスタンスから構成されます。リクエストはクラスタ内の一つのインスタンスに送られます。
そのインスタンスがクラスタ内の他のインスタンスにクエリを伝播し、結果を収集し、そしてクライアントに結果を返します。
従って、大まかに言えば、クラスタまたはクラスタを代表するインスタンスが SQL データベースに相当します。

Elasticsearch ではデータはインデクスに保存されます。インデクスが SQL のテーブルに相当します。

インデクスはドキュメントを保持します。ドキュメントが SQL テーブルの行に相当します。
このエクステンションでは [[yii\elasticsearch\ActiveRecord|ActiveRecord]] はインデクス内のドキュメントを表現します。
ドキュメントをインデクスに保存する操作はインデクシングと呼ばれます。

ドキュメントのスキーマと言うか構造がいわゆるマッピングで定義されます。マッピングが定義するドキュメントのフィールドが SQL のカラムに相当します。
Elasticsearch ではプライマリ・キー・フィールドは特別扱いです。というのは、それを省略することは出来ず、名前も構造も変更できないからです。
その他のフィールドは全面的に構成可能です。


## フィールドの事前マッピング

ドキュメントをインデクスするときに動的に新しいフィールドを作成することは可能ですが、
ドキュメントをインデクスする前にマッピングを定義する方が良い慣習であると考えられています。

一般的には、属性は一旦定義されると、その型を変更することは不可能です。例えば、あるテキスト・フィールドが英語の言語分析器を使用するように構成されている場合、別の言語に切り替えることはインデクス中の全てのドキュメントを再インデクスしない限りは不可能です。
ただし、限定的ですが、マッピングの動的な変更が可能な場合もあります。
詳細は [Elasticsearch のドキュメント](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html#updating-field-mappings)
を参照して下さい。


## ドキュメントの型

元来、Elasticsearch は異なる構造のドキュメントを同じインデクスに保存できるように設計されました。その処理のために「型」の概念が導入されたのです。
しかし、このアプローチはすぐに人気を失いました。結果として、「型」は
[Elasticsearch 7.x で削除されました](https://www.elastic.co/guide/en/elasticsearch/reference/current/removal-of-types.html)。

現在では、インデクスごとに型を一つだけ持つのがベスト・プラクティスです。
技術的なことを言えば、このエクステンションが Elasticsearch 7 以上のために構成されている場合、
[[yii\elasticsearch\ActiveRecord::type()|type()]] は無視されて、API によって「型」が要求される場所では暗黙の内に `_doc` に置き換えられます。


## ヘルパ・メソッドを作成する

私たちが推奨するのは、インデクスの作成と更新を扱う幾つかのスタティックなメソッドを [[yii\elasticsearch\ActiveRecord|ActiveRecord]] モデルに作成することです。
以下に、どのようにしてそれが可能か、一例を示します。

```php
class Customer extends yii\elasticsearch\ActiveRecord
{
    // クラスの他の属性とメソッド
    // ...

    /**
     * @return array このモデルのマッピングを返す
     */
    public static function mapping()
    {
        return [
            // フィールドの型: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#field-datatypes
            'properties' => [
                'first_name'     => ['type' => 'text'],
                'last_name'      => ['type' => 'text'],
                'order_ids'      => ['type' => 'keyword'],
                'email'          => ['type' => 'keyword'],
                'registered_at'  => ['type' => 'date'],
                'updated_at'     => ['type' => 'date'],
                'status'         => ['type' => 'keyword'],
                'is_active'      => ['type' => 'boolean'],
            ]
        ];
    }

    /**
     * このモデルのマッピングを設定（更新）する
     */
    public static function updateMapping()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->setMapping(static::index(), static::type(), static::mapping());
    }

    /**
     * このモデルのインデクスを作成する
     */
    public static function createIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->createIndex(static::index(), [
            //'aliases' => [ /* ... */ ],
            'mappings' => static::mapping(),
            //'settings' => [ /* ... */ ],
        ]);
    }

    /**
     * このモデルのインデクスを削除する
     */
    public static function deleteIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->deleteIndex(static::index(), static::type());
    }
}
```

正しいマッピングでインデクスを作成するためには、`Customer::createIndex()` を呼びます。
マッピングの更新が許容される仕方でマッピングを変更した場合は、`Customer::updateMapping()` を呼びます。

しかし、プロパティを変更（例えば `string` から `date` へ) した場合は、Elasticsearch はマッピングを更新することが出来ません。
この場合は、(`Customer::deleteIndex()` を呼んで) インデクスを削除し、(`Customer::createIndex()` を呼んで) 更新後のマッピングでインデクスを再作成して、
それからデータを再投入する必要があります。
