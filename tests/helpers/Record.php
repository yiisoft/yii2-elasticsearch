<?php
/**
 * @author Aleksandar Panic
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @since 1.0.0
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
