<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\Drivers\Sync;

use Yiisoft\Yii\Queue\Drivers\Sync\Queue;
use Yiisoft\Yii\Queue\Events\ExecEvent;
use Yiisoft\Yii\Queue\Tests\Drivers\TestCase;

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
        return $this->container->get('syncQueue');
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
        $this->getQueue()->on(ExecEvent::BEFORE, $beforeExec);
        $this->getQueue()->run();
        $this->getQueue()->off(ExecEvent::BEFORE, $beforeExec);
        $isDone = $this->getQueue()->isDone($id);

        $this->assertTrue($isWaiting);
        $this->assertTrue($isReserved);
        $this->assertTrue($isDone);
    }
}
