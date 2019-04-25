<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests;

use Yiisoft\Yii\Queue\Closure\Behavior as ClosureBehavior;
use Yiisoft\Yii\Queue\Events\ExecEvent;
use Yiisoft\Yii\Queue\InvalidJobException;
use Yiisoft\Yii\Queue\Events\JobEvent;
use Yiisoft\Yii\Queue\Drivers\Sync\Queue as SyncQueue;
use Yiisoft\Yii\Queue\Serializers\JsonSerializer;

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
        $eventHandler = static function (JobEvent $event) use (&$eventCounter) {
            $eventCounter[$event->id][$event->name] = true;
        };
        $queue = new SyncQueue(new JsonSerializer());
        $queue->strictJobType = false;
        $queue->on(ExecEvent::BEFORE, $eventHandler);
        $queue->on(ExecEvent::AFTER, $eventHandler);
        $queue->on(ExecEvent::AFTER, function (ExecEvent $event) {
            $this->assertInstanceOf(InvalidJobException::class, $event->error);
            $this->assertFalse($event->retry);
        });
        $jobId = $queue->push('message that cannot be unserialized');
        $queue->run();
        $this->assertArrayHasKey($jobId, $eventCounter);
        $this->assertArrayHasKey(ExecEvent::BEFORE, $eventCounter[$jobId]);
        $this->assertArrayHasKey(ExecEvent::AFTER, $eventCounter[$jobId]);
    }

    public function testExecResult()
    {
        $queue = new SyncQueue(new JsonSerializer());
        $queue->attachBehavior('closure', ClosureBehavior::class);
        $isTriggered = false;
        $queue->on(ExecEvent::AFTER, function (ExecEvent $event) use (&$isTriggered) {
            $isTriggered = true;
            $this->assertSame(12345, $event->result);
        });
        $queue->push(static function () {
            return 12345;
        });
        $queue->run();
        $this->assertTrue($isTriggered);
    }
}
