<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Psr\EventDispatcher\ListenerProviderInterface;
use RuntimeException;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Console\Config\EventConfigurator;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Message;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\ExceptionalSimpleJob;
use Yiisoft\Yii\Queue\Tests\App\SimpleJob;
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
        $job = new SimpleJob();
        $message = new Message('', $job);
        $queue = $this->createMock(Queue::class);

        $this->worker->process($message, $queue);
        $this->assertEquals(true, $job->executed);
    }

    /**
     * Check job execution is prevented
     */
    public function testJobNotExecuted(): void
    {
        $handler = fn (BeforeExecution $event) => $event->stopExecution();
        $configurator = $this->container->get(EventConfigurator::class);
        $configurator->registerListeners([BeforeExecution::class => [$handler]]);

        $job = new SimpleJob();
        $message = new Message('', $job);
        $queue = $this->createMock(Queue::class);

        $this->worker->process($message, $queue);
        $this->assertEquals(false, $job->executed);
    }

    /**
     * Check job throws exception
     */
    public function testThrowException(): void
    {
        $this->expectException(RuntimeException::class);

        $job = new ExceptionalSimpleJob();
        $message = new Message('', $job);
        $queue = $this->createMock(Queue::class);
        $this->worker->process($message, $queue);
    }

    /**
     * Check exception throwing is prevented
     */
    public function testThrowExceptionPrevented(): void
    {
        $handler = fn (JobFailure $event) => $event->preventThrowing();
        /** @var EventConfigurator $configurator */
        $configurator = $this->container->get(EventConfigurator::class);
        $configurator->registerListeners([JobFailure::class => [$handler]]);

        $job = new ExceptionalSimpleJob();
        $message = new Message('', $job);
        $queue = $this->createMock(Queue::class);
        $this->worker->process($message, $queue);
        $this->assertEquals(true, $job->executed);
    }
}
