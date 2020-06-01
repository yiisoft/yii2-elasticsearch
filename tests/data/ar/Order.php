<?php

namespace yiiunit\extensions\elasticsearch\data\ar;

use yii\elasticsearch\ActiveQuery;
use yii\elasticsearch\Command;

/**
 * Class Order
 *
 * @property integer $id
 * @property integer $customer_id
 * @property integer $created_at
 * @property string $total
 * @property array $itemsArray
 *
 * @property-read Item[] $expensiveItemsUsingViaWithCallable
 * @property-read Item[] $cheapItemsUsingViaWithCallable
 * @property-read Item[] $itemsByArrayValue
 */
class Order extends ActiveRecord
{
    public function attributes()
    {
        return ['customer_id', 'created_at', 'total', 'itemsArray'];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['_id' => 'customer_id']);
    }

    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::className(), ['order_id' => '_id']);
    }

    /**
     * A relation to Item defined via array valued attribute
     */
    public function getItemsByArrayValue()
    {
        return $this->hasMany(Item::className(), ['_id' => 'itemsArray'])->indexBy('_id');
    }

    public function getItems()
    {
        return $this->hasMany(Item::className(), ['_id' => 'item_id'])
            ->via('orderItems')->orderBy('_id');
    }

    public function getExpensiveItemsUsingViaWithCallable()
    {
        return $this->hasMany(Item::className(), ['_id' => 'item_id'])
            ->via('orderItems', function (ActiveQuery $q) {
                $q->where(['>=', 'subtotal', 10]);
            });
    }

    public function getCheapItemsUsingViaWithCallable()
    {
        return $this->hasMany(Item::className(), ['_id' => 'item_id'])
            ->via('orderItems', function (ActiveQuery $q) {
                $q->where(['<', 'subtotal', 10]);
            });
    }

    public function getItemsIndexed()
    {
        return $this->hasMany(Item::className(), ['_id' => 'item_id'])
            ->via('orderItems')->indexBy('_id');
    }

    public function getItemsInOrder1()
    {
        return $this->hasMany(Item::className(), ['_id' => 'item_id'])
            ->via('orderItems', function ($q) {
                $q->orderBy(['subtotal' => SORT_ASC]);
            })->orderBy('name');
    }

    public function getItemsInOrder2()
    {
        return $this->hasMany(Item::className(), ['_id' => 'item_id'])
            ->via('orderItems', function ($q) {
                $q->orderBy(['subtotal' => SORT_DESC]);
            })->orderBy('name');
    }

    public function getBooks()
    {
        return $this->hasMany(Item::className(), ['_id' => 'item_id'])
            ->via('orderItems')
            ->where(['category_id' => 1]);
    }

    /**
     * sets up the index for this record
     * @param Command $command
     */
    public static function setUpMapping($command)
    {
        $command->setMapping(static::index(), static::type(), [
            'properties' => [
                'customer_id' => ['type' => 'integer'],
                'total' => ['type' => 'integer'],
            ]
        ]);
    }
}
