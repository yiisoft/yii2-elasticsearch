Mapping & Indexing
============

## Setup Mapping

In case you using ElasticSearch ActiveRecord , you could define a method for setupMapping

```
php

Class BookIndex extends yii\elasticsearch\ActiveRecord
{
    /**
     * sets up the index for this record
     *
     */
    public static function setUpMapping()
    {
        $db = static::getDb();

        //in case you are not using elasticsearch ActiveRecord so current class extends database ActiveRecord yii/db/activeRecord
        // $db = yii\elasticsearch\ActiveRecord::getDb();

        $command = $db->createCommand();

        /*
         * you can delete the current mapping for fresh mapping but this not recommended and can be dangrous.
         */

        // $command->deleteMapping(static::index(), static::type());

        $command->setMapping(static::index(), static::type(), [
            static::type() => [
                // "_id" => ["path" => "id", "store" => "yes"],
                "properties" => [
                    'name'           => ["type" => "string"],
                    'author_name'    => ["type" => "string"],
                    'publisher_name' => ["type" => "string"],
                    'created_at'     => ["type" => "long"],
                    'updated_at'     => ["type" => "long"],
                    'status'         => ["type" => "long"],

                ],
            ],
        ]);
    }
}

```
Later on you just need to call this method any time you want to apply the new mapping.

## Indexing
TBD
