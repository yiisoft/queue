<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Yiisoft\Yii\Event\EventDispatcherProvider;
use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\App\EventHandler;
use Yiisoft\Yii\Queue\Tests\App\QueueHandler;
use Yiisoft\Yii\Queue\Tests\App\RetryablePayload;
use Yiisoft\Yii\Queue\Tests\App\SimplePayload;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class QueueTest extends TestCase
{
    /**
     * @var MockObject|EventHandler
     */
    private MockObject $eventManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventsRegister();
    }

    protected function getEventHandlers(): array
    {
        // TODO check if we need any of these handlers
        // TODO new event provider doesn't support division of handlers by event

        return [
            BeforePush::class => [[$this->eventManager, 'beforePushHandler']],
            AfterPush::class => [[$this->eventManager, 'afterPushHandler']],
            BeforeExecution::class => [[$this->eventManager, 'beforeExecutionHandler']],
            AfterExecution::class => [[$this->eventManager, 'afterExecutionHandler']],
            JobFailure::class => [
                [Queue::class, 'jobRetry'],
                [$this->eventManager, 'jobFailureHandler'],
            ],
        ];
    }

    public function testPushSuccessful(): void
    {
        $this->eventManager->expects(self::once())->method('beforePushHandler');
        $this->eventManager->expects(self::once())->method('afterPushHandler');
        $this->eventManager->expects(self::never())->method('beforeExecutionHandler');
        $this->eventManager->expects(self::never())->method('afterExecutionHandler');
        $this->eventManager->expects(self::never())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimplePayload::class);
        $id = $queue->push($job);

        $this->assertNotEquals('', $id, 'Pushed message should has an id');
    }

    public function testPushNotSuccessful(): void
    {
        $this->expectException(PayloadNotSupportedException::class);
        $this->eventManager->expects(self::once())->method('beforePushHandler');
        $this->eventManager->expects(self::never())->method('afterPushHandler');
        $this->eventManager->expects(self::never())->method('beforeExecutionHandler');
        $this->eventManager->expects(self::never())->method('afterExecutionHandler');
        $this->eventManager->expects(self::never())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(DelayablePayload::class);
        $queue->push($job);
    }

    public function testRun(): void
    {
        $this->eventManager->expects(self::exactly(2))->method('beforePushHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterPushHandler');
        $this->eventManager->expects(self::exactly(2))->method('beforeExecutionHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterExecutionHandler');
        $this->eventManager->expects(self::never())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimplePayload::class);
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
        $queue->run();

        $this->assertEquals(2, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }

    public function testRunPartly(): void
    {
        $this->eventManager->expects(self::exactly(2))->method('beforePushHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterPushHandler');
        $this->eventManager->expects(self::once())->method('beforeExecutionHandler');
        $this->eventManager->expects(self::once())->method('afterExecutionHandler');
        $this->eventManager->expects(self::never())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimplePayload::class);
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
        $queue->run(1);

        $this->assertEquals(1, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }

    public function testListen(): void
    {
        $this->eventManager->expects(self::exactly(2))->method('beforePushHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterPushHandler');
        $this->eventManager->expects(self::exactly(2))->method('beforeExecutionHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterExecutionHandler');
        $this->eventManager->expects(self::never())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimplePayload::class);
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
        $queue->listen();

        $this->assertEquals(2, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }

    public function testJobRetry(): void
    {
        $this->eventManager->expects(self::exactly(2))->method('beforePushHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterPushHandler');
        $this->eventManager->expects(self::exactly(2))->method('beforeExecutionHandler');
        $this->eventManager->expects(self::exactly(2))->method('jobFailureHandler');
        $this->eventManager->expects(self::never())->method('afterExecutionHandler');

        $queue = $this->container->get(Queue::class);
        $payload = $this->container->get(RetryablePayload::class);
        $queue->push($payload);

        try {
            $queue->run();
        } catch (RuntimeException $exception) {
            $this->assertEquals('Test exception', $exception->getMessage());
        }

        $this->assertEquals(2, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }

    public function testJobRetryFail(): void
    {
        $this->eventManager->expects(self::once())->method('beforePushHandler');
        $this->eventManager->expects(self::once())->method('afterPushHandler');
        $this->eventManager->expects(self::once())->method('beforeExecutionHandler');
        $this->eventManager->expects(self::never())->method('afterExecutionHandler');
        $this->eventManager->expects(self::once())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $payload = $this->container->get(RetryablePayload::class);
        $payload->setName('not-supported');
        $queue->push($payload);

        try {
            $queue->run();
        } catch (PayloadNotSupportedException $exception) {
            $message = SynchronousDriver::class . ' does not support payload "retryable".';
            $this->assertEquals($message, $exception->getMessage());
        }

        $this->assertEquals(0, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }

    public function testStatus(): void
    {
        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimplePayload::class);
        $id = $queue->push($job);

        $status = $queue->status($id);
        $this->assertEquals(true, $status->isWaiting());

        $queue->run();
        $status = $queue->status($id);
        $this->assertEquals(true, $status->isDone());
    }
}
