<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests;

use Yiisoft\Yii\Queue\Closure\Behavior as ClosureBehavior;
use Yiisoft\Yii\Queue\ExecEvent;
use Yiisoft\Yii\Queue\InvalidJobException;
use Yiisoft\Yii\Queue\JobEvent;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Drivers\Sync\Queue as SyncQueue;

/**
 * Job Event Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JobEventTest extends TestCase
{
    public function testInvalidJob()
    {
        $eventCounter = [];
        $eventHandler = function (JobEvent $event) use (&$eventCounter) {
            $eventCounter[$event->id][$event->name] = true;
        };
        $queue = new SyncQueue(['strictJobType' => false]);
        $queue->on(Queue::EVENT_BEFORE_EXEC, $eventHandler);
        $queue->on(Queue::EVENT_AFTER_ERROR, $eventHandler);
        $queue->on(Queue::EVENT_AFTER_ERROR, function (ExecEvent $event) {
            $this->assertTrue($event->error instanceof InvalidJobException);
            $this->assertFalse($event->retry);
        });
        $jobId = $queue->push('message that cannot be unserialized');
        $queue->run();
        $this->assertArrayHasKey($jobId, $eventCounter);
        $this->assertArrayHasKey(Queue::EVENT_BEFORE_EXEC, $eventCounter[$jobId]);
        $this->assertArrayHasKey(Queue::EVENT_AFTER_ERROR, $eventCounter[$jobId]);
    }

    public function testExecResult()
    {
        $queue = new SyncQueue(['as closure' => ClosureBehavior::class]);
        $isTriggered = false;
        $queue->on(Queue::EVENT_AFTER_EXEC, function (ExecEvent $event) use (&$isTriggered) {
            $isTriggered = true;
            $this->assertSame(12345, $event->result);
        });
        $queue->push(function () {
            return 12345;
        });
        $queue->run();
        $this->assertTrue($isTriggered);
    }
}
