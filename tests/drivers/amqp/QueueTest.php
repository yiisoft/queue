<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\tests\drivers\amqp;

use yii\helpers\Yii;
use yii\queue\tests\drivers\CliTestCase;
use yii\queue\amqp\Queue;

/**
 * AMQP Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends CliTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->amqpQueue;
    }

    public function testListen()
    {
        $this->startProcess('php yii queue/listen');
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }
}
