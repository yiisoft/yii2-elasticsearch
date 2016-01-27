Mapping & Indexing
============

## Setup Mapping

In case you using ElasticSearch ActiveRecord , you could define a method for setupMapping

```
php

Class BookIndex extends yii\elasticsearch\ActiveRecord;

     /**
     * sets up the index for this record
     * @param Command $command
     */
    public static function setUpMapping()
    {
        $db = $this->getDb();
        
        //in case you are not using elasticsearch ActivRecord so current class extends database ActiveRecord yii/db/activeRecord
        //$db=yii/elasticsearch/ActiveRecord::getDb();
        
        $command = $db->createCommand();
        $command->deleteMapping(static::index(), static::type());
        $command->setMapping(static::index(), static::type(), [
            static::type() => [
                // "_id" => ["path" => "id", "store" => "yes"],
                "properties" => [
                    'name' => ["type" => "string"],
                    'author_name' => ["type" => "string"],
                    'publisher_name' => ["type" => "string"],         
                    'created_at' => ["type" => "long"],
                    'updated_at' => ["type" => "long"],
                    'status' => ["type" => "long"],
                 
                ]
            ]
        ]);
    }             

```
Later on you just need to call this method any time you want to apply the new mapping.

## Indexing
TBD
