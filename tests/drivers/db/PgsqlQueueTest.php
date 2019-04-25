<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\drivers\db;

use yii\helpers\Yii;
use Yiisoft\Yii\Queue\Drivers\Db\Queue;

/**
 * Postgres Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PgsqlQueueTest extends TestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->pgsqlQueue;
    }
}
