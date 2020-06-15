<?php

namespace yiiunit\extensions\elasticsearch\data\ar;

use yii\elasticsearch\Command;
use yiiunit\extensions\elasticsearch\ActiveRecordTest;

/**
 * Class Customer
 *
 * @property integer $id
 * @property string $name
 * @property string $email
 * @property string $address
 * @property integer $status
 * @property bool $is_active
 */
class Customer extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    public function attributes()
    {
        return ['name', 'email', 'address', 'status', 'is_active'];
    }

    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => '_id'])->orderBy('created_at');
    }

    public function getExpensiveOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => '_id'])
            ->where([ 'gte', 'total', 50 ])
            ->orderBy('_id');
    }

    public function getOrdersWithItems()
    {
        return $this->hasMany(Order::className(), ['customer_id' => '_id'])->with('orderItems');
    }

    public function afterSave($insert, $changedAttributes)
    {
        ActiveRecordTest::$afterSaveInsert = $insert;
        ActiveRecordTest::$afterSaveNewRecord = $this->isNewRecord;
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * sets up the index for this record
     * @param Command $command
     * @param boolean $statusIsBoolean
     */
    public static function setUpMapping($command)
    {
        $command->setMapping(static::index(), static::type(), [
            "properties" => [
                "name" => ["type" => "keyword",  "store" => true],
                "email" => ["type" => "keyword", "store" => true],
                "address" => ["type" => "text"],
                "status" => ["type" => "integer", "store" => true],
                "is_active" => ["type" => "boolean", "store" => true],
            ]
        ]);

    }

    /**
     * @inheritdoc
     * @return CustomerQuery
     */
    public static function find()
    {
        return new CustomerQuery(get_called_class());
    }
}
