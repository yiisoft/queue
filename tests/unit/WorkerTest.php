<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use RuntimeException;
use Yiisoft\Yii\Event\EventConfigurator;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Message;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\QueueHandler;
use Yiisoft\Yii\Queue\Tests\TestCase;
use Yiisoft\Yii\Queue\Worker\Worker;

class WorkerTest extends TestCase
{
    /**
     * @var Worker
     */
    private $worker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->worker = $this->container->get(Worker::class);
    }

    /**
     * Check normal job execution
     */
    public function testJobExecuted(): void
    {
        $message = new Message('simple', '', []);
        $queue = $this->createMock(Queue::class);

        $this->worker->process($message, $queue);
        $this->assertEquals(1, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }

    /**
     * Check job execution is prevented
     */
    public function testJobNotExecuted(): void
    {
        $handler = fn (BeforeExecution $event) => $event->stopExecution();
        $this->container->get(EventConfigurator::class)->registerListeners([BeforeExecution::class => [$handler]]);

        $message = new Message('simple', '', []);
        $queue = $this->createMock(Queue::class);
        $this->worker->process($message, $queue);

        $this->assertEquals(0, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }

    /**
     * Check job throws exception
     */
    public function testThrowException(): void
    {
        $this->expectException(RuntimeException::class);

        $message = new Message('exceptional', '', []);
        $queue = $this->createMock(Queue::class);
        $this->worker->process($message, $queue);
    }

    /**
     * Check exception throwing is prevented
     */
    public function testThrowExceptionPrevented(): void
    {
        $handler = fn (JobFailure $event) => $event->preventThrowing();
        $this->container->get(EventConfigurator::class)->registerListeners([JobFailure::class => [$handler]]);

        $message = new Message('exceptional', '', []);
        $queue = $this->createMock(Queue::class);
        $this->worker->process($message, $queue);

        $this->assertEquals(1, $this->container->get(QueueHandler::class)->getJobExecutionTimes());
    }
}
