<?php
/**
 * @author Aleksandar Panic
 **/

namespace yii\elasticsearch\tests\helpers;


use yii\elasticsearch\ActiveRecord;

class Record
{
    public static function insert($modelClass, $data)
    {
        /** @var ActiveRecord $model */
        $model = new $modelClass($data);
        $model->save(false);

        return $model;
    }


    public static function insertMany($modelClass, $rows)
    {
        $results = [];

        foreach ($rows as $row) {
            $results[] = static::insert($modelClass, $row);
        }

        return $results;
    }

    public static function initIndex($class, $db)
    {
        $index = $class::index();

        if ($db->createCommand()->indexExists($index)) {
            $db->createCommand()->deleteIndex($index);
        }
        $db->createCommand()->createIndex($index);

        $class::setUpMapping($db->createCommand());
    }

    public static function refreshIndex($class, $db)
    {
        $db->createCommand()->refreshIndex($class::index());
    }
}
