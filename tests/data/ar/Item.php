<?php

namespace yiiunit\extensions\elasticsearch\data\ar;

use yii\elasticsearch\Command;

/**
 * Class Item
 *
 * @property integer $id
 * @property string $name
 * @property integer $category_id
 */
class Item extends ActiveRecord
{
    public static function primaryKey()
    {
        return ['id'];
    }

    public function attributes()
    {
        return ['id', 'name', 'category_id'];
    }

    /**
     * sets up the index for this record
     * @param Command $command
     */
    public static function setUpMapping($command)
    {
        $command->deleteMapping(static::index(), static::type());
        $command->setMapping(static::index(), static::type(), [
            static::type() => [
                "properties" => [
                    "name" =>        ["type" => "string", "index" => "not_analyzed"],
                    "category_id" =>      ["type" => "integer"],
                ]
            ]
        ]);

    }
}
