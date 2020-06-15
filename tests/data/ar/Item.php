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
    public function attributes()
    {
        return ['name', 'category_id'];
    }

    /**
     * sets up the index for this record
     * @param Command $command
     */
    public static function setUpMapping($command)
    {
        $command->setMapping(static::index(), static::type(), [
            "properties" => [
                "name" => ["type" => "keyword", "store" => true],
                "category_id" => ["type" => "integer"],
            ]
        ]);

    }
}
