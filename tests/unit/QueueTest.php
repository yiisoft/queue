<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use PHPUnit\Framework\MockObject\MockObject;
use Yiisoft\Yii\Event\EventConfigurator;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\App\EventManager;
use Yiisoft\Yii\Queue\Tests\App\QueueHandler;
use Yiisoft\Yii\Queue\Tests\App\RetryablePayload;
use Yiisoft\Yii\Queue\Tests\App\SimplePayload;
use Yiisoft\Yii\Queue\Tests\TestCase;

class QueueTest extends TestCase
{
    /**
     * @var MockObject|EventManager
     */
    private MockObject $eventManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventManager = $this->createMock(EventManager::class);

        $configurator = $this->container->get(EventConfigurator::class);
        $configurator->registerListeners([BeforePush::class => [[$this->eventManager, 'beforePushHandler']]]);
        $configurator->registerListeners([AfterPush::class => [[$this->eventManager, 'afterPushHandler']]]);
        $configurator->registerListeners([BeforeExecution::class => [[$this->eventManager, 'beforeExecutionHandler']]]);
        $configurator->registerListeners([AfterExecution::class => [[$this->eventManager, 'afterExecutionHandler']]]);
        $configurator->registerListeners([JobFailure::class => [[$this->eventManager, 'jobFailureHandler']]]);
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
        $job = $this->container->get(SimpleJob::class);
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
        $queue->run();

        $this->assertTrue($job->executed);
        $this->assertTrue($job2->executed);
    }

    public function testRunPartly(): void
    {
        $this->eventManager->expects(self::exactly(2))->method('beforePushHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterPushHandler');
        $this->eventManager->expects(self::once())->method('beforeExecutionHandler');
        $this->eventManager->expects(self::once())->method('afterExecutionHandler');
        $this->eventManager->expects(self::never())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimpleJob::class);
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
        $queue->run(1);

        $this->assertTrue($job->executed);
        $this->assertFalse($job2->executed);
    }

    public function testListen(): void
    {
        $this->eventManager->expects(self::exactly(2))->method('beforePushHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterPushHandler');
        $this->eventManager->expects(self::exactly(2))->method('beforeExecutionHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterExecutionHandler');
        $this->eventManager->expects(self::never())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimpleJob::class);
        $job2 = clone $job;
        $queue->push($job);
        $queue->push($job2);
        $queue->listen();

        $this->assertTrue($job->executed);
        $this->assertTrue($job2->executed);
    }

    public function testJobRetry(): void
    {
        $this->eventManager->expects(self::exactly(2))->method('beforePushHandler');
        $this->eventManager->expects(self::exactly(2))->method('afterPushHandler');
        $this->eventManager->expects(self::exactly(2))->method('beforeExecutionHandler');
        $this->eventManager->expects(self::once())->method('afterExecutionHandler');
        $this->eventManager->expects(self::once())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $payload = $this->container->get(RetryablePayload::class);
        $queue->push($payload);
        $queue->run();

        $this->assertEquals(1, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
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
