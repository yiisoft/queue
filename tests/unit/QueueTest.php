<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use RuntimeException;
use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\App\RetryablePayload;
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

        $this->assertEquals(2, $this->executionTimes);

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

        $this->assertEquals(1, $this->executionTimes);

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

        $this->assertEquals(2, $this->executionTimes);

        $events = [
            BeforePush::class => 2,
            AfterPush::class => 2,
            BeforeExecution::class => 2,
            AfterExecution::class => 2,
        ];
        $this->assertEvents($events);
    }

    public function testJobRetry(): void
    {
        $this->markTestSkipped('The logic will be refactored in https://github.com/yiisoft/yii-queue/issues/59');

        $exception = null;

        $queue = $this->getQueue();
        $payload = new RetryablePayload();
        $queue->push($payload);

        try {
            $queue->run();
        } catch (RuntimeException $exception) {
        } finally {
            $this->assertInstanceOf(RuntimeException::class, $exception);
            $this->assertEquals(
                "Processing of message #0 is stopped because of an exception:\ntest.",
                $exception->getMessage()
            );
            $this->assertEquals(2, $this->executionTimes);

            $events = [
                BeforePush::class => 2,
                AfterPush::class => 2,
                BeforeExecution::class => 2,
                JobFailure::class => 2,
            ];
            $this->assertEvents($events);
        }
    }

    public function testJobRetryFail(): void
    {
        $this->markTestSkipped('The logic will be refactored in https://github.com/yiisoft/yii-queue/issues/59');

        $queue = $this->getQueue();
        $payload = new RetryablePayload();
        $payload->setName('not-supported');
        $queue->push($payload);
        $exception = null;

        try {
            $queue->run();
        } catch (PayloadNotSupportedException $exception) {
        } finally {
            $message = SynchronousDriver::class . ' does not support payload "retryable".';
            $this->assertInstanceOf(PayloadNotSupportedException::class, $exception);
            $this->assertEquals($message, $exception->getMessage());
            $this->assertEquals(0, $this->executionTimes);

            $events = [
                BeforePush::class => 1,
                AfterPush::class => 1,
                BeforeExecution::class => 1,
                JobFailure::class => 1,
            ];
            $this->assertEvents($events);
        }
    }

    public function testStatus(): void
    {
        $queue = $this->getQueue();
        $job = new SimplePayload();
        $id = $queue->push($job);

        $status = $queue->status($id);
        $this->assertTrue($status->isWaiting());

        $queue->run();
        $status = $queue->status($id);
        $this->assertTrue($status->isDone());
    }
}
