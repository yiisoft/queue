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
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

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
        $this->container->get(WorkerInterface::class)->registerHandlers(['simple' => [QueueHandler::class, 'simple']]);

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
        $this->container->get(WorkerInterface::class)->registerHandlers(['simple' => [QueueHandler::class, 'simple']]);

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

    public function testJobRetry(): void
    {
        $this->container->get(WorkerInterface::class)->registerHandlers(['retryable' => [QueueHandler::class, 'retryable']]);

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
        $this->container->get(WorkerInterface::class)->registerHandlers(['simple' => [QueueHandler::class, 'simple']]);

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
