<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\elasticsearch;

/**
 * Exception represents an exception that is caused by Elasticsearch-related operations.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Exception extends \yii\db\Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Elasticsearch Database Exception';
    }
}
