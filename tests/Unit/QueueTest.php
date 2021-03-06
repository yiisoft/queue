<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\Behaviors\DelayBehavior;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class QueueTest extends TestCase
{
    private bool $needsRealAdapter = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->needsRealAdapter = true;
    }

    protected function needsRealAdapter(): bool
    {
        return $this->needsRealAdapter;
    }

    public function testPushSuccessful(): void
    {
        $this->needsRealAdapter = false;

        $queue = $this->getQueue();
        $message = new Message('simple', null);
        $queue->push($message);

        $this->assertEvents([BeforePush::class => 1, AfterPush::class => 1]);
    }

    public function testPushNotSuccessful(): void
    {
        $this->needsRealAdapter = false;
        $behavior = new DelayBehavior(2);
        $exception = new BehaviorNotSupportedException(get_class($this->getAdapter()), $behavior);
        $this->getAdapter()->method('push')->willThrowException($exception);
        $expectedException = null;

        $queue = $this->getQueue();
        $message = new Message('simple', null);
        try {
            $queue->push($message);
        } catch (BehaviorNotSupportedException $expectedException) {
        } finally {
            self::assertInstanceOf(BehaviorNotSupportedException::class, $expectedException);
            $this->assertEvents([BeforePush::class => 1]);
        }
    }

    public function testRun(): void
    {
        $queue = $this->getQueue();
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run();

        self::assertEquals(2, $this->executionTimes);

        $events = [
            BeforePush::class => 2,
            AfterPush::class => 2,
            BeforeExecution::class => 2,
            AfterExecution::class => 2,
        ];
        $this->assertEvents($events);
    }

    public function testRunPartly(): void
    {
        $queue = $this->getQueue();
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run(1);

        self::assertEquals(1, $this->executionTimes);

        $events = [
            BeforePush::class => 2,
            AfterPush::class => 2,
            BeforeExecution::class => 1,
            AfterExecution::class => 1,
        ];
        $this->assertEvents($events);
    }

    public function testListen(): void
    {
        $queue = $this->getQueue();
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->listen();

        self::assertEquals(2, $this->executionTimes);

        $events = [
            BeforePush::class => 2,
            AfterPush::class => 2,
            BeforeExecution::class => 2,
            AfterExecution::class => 2,
        ];
        $this->assertEvents($events);
    }

    public function testStatus(): void
    {
        $queue = $this->getQueue();
        $message = new Message('simple', null);
        $queue->push($message);
        $id = $message->getId();

        $status = $queue->status($id);
        self::assertTrue($status->isWaiting());

        $queue->run();
        $status = $queue->status($id);
        self::assertTrue($status->isDone());
    }
}
