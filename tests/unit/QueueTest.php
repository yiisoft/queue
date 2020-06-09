<?php
declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use PHPUnit\Framework\MockObject\MockObject;
use Yiisoft\Yii\Console\Config\EventConfigurator;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\JobNotSupportedException;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\DelayableJob;
use Yiisoft\Yii\Queue\Tests\App\EventManager;
use Yiisoft\Yii\Queue\Tests\App\RetryableJob;
use Yiisoft\Yii\Queue\Tests\App\SimpleJob;
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

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimpleJob::class);
        $id = $queue->push($job);

        $this->assertNotEmpty($id, 'Pushed message should has an id');
    }

    public function testPushNotSuccessful(): void
    {
        $this->expectException(JobNotSupportedException::class);
        $this->eventManager->expects(self::once())->method('beforePushHandler');
        $this->eventManager->expects(self::never())->method('afterPushHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(DelayableJob::class);
        $queue->push($job);
    }

    public function testJobRetry(): void
    {
        $this->eventManager->expects(self::once())->method('jobFailureHandler');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(RetryableJob::class);
        $queue->push($job);
        $queue->run();

        $this->assertTrue($job->executed);
    }

    public function testStatus(): void
    {
        $queue = $this->container->get(Queue::class);
        $job = $this->container->get(SimpleJob::class);
        $id = $queue->push($job);

        $queue->status($id);
    }
}
