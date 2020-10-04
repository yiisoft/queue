<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\App\SimplePayload;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class QueueTest extends TestCase
{
    private bool $needsRealDriver = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->needsRealDriver = true;
    }

    protected function needsRealDriver(): bool
    {
        return $this->needsRealDriver;
    }

    public function testPushSuccessful(): void
    {
        $this->needsRealDriver = false;
        $this->getDriver()->method('canPush')->willReturn(true);

        $queue = $this->getQueue();
        $job = new SimplePayload();
        $queue->push($job);

        $this->assertEvents([BeforePush::class => 1, AfterPush::class => 1]);
    }

    public function testPushNotSuccessful(): void
    {
        $this->needsRealDriver = false;
        $this->getDriver()->method('canPush')->willReturn(false);
        $exception = null;

        $queue = $this->getQueue();
        $job = new DelayablePayload();
        try {
            $queue->push($job);
        } catch (PayloadNotSupportedException $exception) {
        } finally {
            self::assertInstanceOf(PayloadNotSupportedException::class, $exception);
            $this->assertEvents([BeforePush::class => 1]);
        }
    }

    public function testRun(): void
    {
        $queue = $this->getQueue();
        $job = new SimplePayload();
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
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
        $job = new SimplePayload();
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
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
        $job = new SimplePayload();
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
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
        $job = new SimplePayload();
        $id = $queue->push($job);

        $status = $queue->status($id);
        self::assertTrue($status->isWaiting());

        $queue->run();
        $status = $queue->status($id);
        self::assertTrue($status->isDone());
    }
}
