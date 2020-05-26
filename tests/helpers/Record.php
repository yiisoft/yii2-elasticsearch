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
}
