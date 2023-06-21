<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\extensions\elasticsearch\data\ar;

/**
 * Class Animal
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 * @since 2.0
 */
class Animal extends ActiveRecord
{

    public $does;

    public static function index()
    {
        return 'animals';
    }

    public static function type()
    {
        return 'animal';
    }

    public function attributes()
    {
        return ['species'];
    }

    /**
     * sets up the index for this record
     * @param Command $command
     */
    public static function setUpMapping($command)
    {
        $command->setMapping(static::index(), static::type(), [
            "properties" => [
                "species" => ["type" => "keyword"]
            ]
        ]);
    }

    public function init()
    {
        parent::init();
        $this->species = get_called_class();
    }

    public function getDoes()
    {
        return $this->does;
    }

    /**
     *
     * @param type $row
     * @return \yiiunit\data\ar\elasticsearch\Animal
     */
    public static function instantiate($row)
    {
        $class = $row['_source']['species'];
        return new $class;
    }

}
