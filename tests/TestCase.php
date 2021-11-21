<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SimpleLoop;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Worker\Worker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/**
 * Base Test Case.
 */
abstract class TestCase extends BaseTestCase
{
    protected ?ContainerInterface $container = null;
    protected ?Queue $queue = null;
    protected ?AdapterInterface $adapter = null;
    protected ?LoopInterface $loop = null;
    protected ?WorkerInterface $worker = null;
    protected ?EventDispatcherInterface $dispatcher = null;
    protected array $eventHandlers = [];
    protected int $executionTimes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = null;
        $this->queue = null;
        $this->adapter = null;
        $this->loop = null;
        $this->worker = null;
        $this->dispatcher = null;
        $this->eventHandlers = [];
        $this->executionTimes = 0;
    }

    protected function getQueue(): Queue
    {
        if ($this->queue === null) {
            $this->queue = $this->createQueue();
        }

        return $this->queue;
    }

    /**
     * @return AdapterInterface|MockObject
     */
    protected function getAdapter(): AdapterInterface
    {
        if ($this->adapter === null) {
            $this->adapter = $this->createAdapter($this->needsRealAdapter());
        }

        return $this->adapter;
    }

    protected function getLoop(): LoopInterface
    {
        if ($this->loop === null) {
            $this->loop = $this->createLoop();
        }

        return $this->loop;
    }

    protected function getWorker(): WorkerInterface
    {
        if ($this->worker === null) {
            $this->worker = $this->createWorker();
        }

        return $this->worker;
    }

    protected function getEventDispatcher(): SimpleEventDispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->createEventDispatcher();
        }

        return $this->dispatcher;
    }

    protected function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    protected function createQueue(): Queue
    {
        return new Queue(
            $this->getEventDispatcher(),
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger()
        );
    }

    protected function createAdapter(bool $realAdapter = false): AdapterInterface
    {
        if ($realAdapter) {
            return new SynchronousAdapter($this->getWorker(), $this->createQueue());
        }

        return $this->createMock(AdapterInterface::class);
    }

    protected function createLoop(): LoopInterface
    {
        return new SimpleLoop($this->getEventDispatcher());
    }

    protected function createWorker(): WorkerInterface
    {
        return new Worker(
            $this->getMessageHandlers(),
            $this->getEventDispatcher(),
            new NullLogger(),
            new Injector($this->getContainer()),
            $this->getContainer()
        );
    }

    protected function createEventDispatcher(): SimpleEventDispatcher
    {
        return new SimpleEventDispatcher(...$this->getEventHandlers());
    }

    protected function createContainer(): ContainerInterface
    {
        return new SimpleContainer($this->getContainerDefinitions());
    }

    protected function getContainerDefinitions(): array
    {
        return [];
    }

    protected function setEventHandlers(callable ...$handlers): void
    {
        $this->eventHandlers = $handlers;
    }

    protected function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }

    protected function getMessageHandlers(): array
    {
        return [
            'simple' => fn () => $this->executionTimes++,
            'exceptional' => function () {
                $this->executionTimes++;

                throw new RuntimeException('test');
            },
            'retryable' => function () {
                $this->executionTimes++;

                throw new RuntimeException('test');
            },
        ];
    }

    protected function needsRealAdapter(): bool
    {
        return false;
    }

    protected function assertEvents(array $events = []): void
    {
        $default = [
            BeforePush::class => 0,
            AfterPush::class => 0,
            BeforeExecution::class => 0,
            AfterExecution::class => 0,
            JobFailure::class => 0,
        ];
        foreach (array_merge($default, $events) as $event => $timesExecuted) {
            self::assertEquals($timesExecuted, $this->getEventsCount($event));
        }
    }

    protected function getEventsCount(string $className): int
    {
        $result = 0;
        foreach ($this->getEventDispatcher()->getEvents() as $event) {
            if ($event instanceof $className) {
                $result++;
            }
        }

        return $result;
    }
}
