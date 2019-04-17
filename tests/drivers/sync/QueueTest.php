<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\tests\drivers\sync;

use yii\helpers\Yii;
use yii\queue\sync\Queue;
use yii\queue\tests\drivers\TestCase;

/**
 * Sync Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends TestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->syncQueue;
    }

    public function testRun()
    {
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->getQueue()->run();

        $this->assertSimpleJobDone($job);
    }

    public function testStatus()
    {
        $job = $this->createSimpleJob();
        $id = $this->getQueue()->push($job);
        $isWaiting = $this->getQueue()->isWaiting($id);
        $isReserved = false;
        $beforeExec = function () use ($id, &$isReserved) {
            $isReserved = $this->getQueue()->isReserved($id);
        };
        $this->getQueue()->on(Queue::EVENT_BEFORE_EXEC, $beforeExec);
        $this->getQueue()->run();
        $this->getQueue()->off(Queue::EVENT_BEFORE_EXEC, $beforeExec);
        $isDone = $this->getQueue()->isDone($id);

        $this->assertTrue($isWaiting);
        $this->assertTrue($isReserved);
        $this->assertTrue($isDone);
    }
}
