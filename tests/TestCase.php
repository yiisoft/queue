<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\RetryablePayload;
use Yiisoft\Yii\Queue\Worker\Worker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/**
 * Base Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends BaseTestCase
{
    protected ?ContainerInterface $container = null;
    protected ?Queue $queue = null;
    protected ?DriverInterface $driver = null;
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
        $this->driver = null;
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
     * @return DriverInterface|MockObject
     */
    protected function getDriver(): DriverInterface
    {
        if ($this->driver === null) {
            $this->driver = $this->createDriver($this->needsRealDriver());
        }

        return $this->driver;
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
            $this->getDriver(),
            $this->getEventDispatcher(),
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger()
        );
    }

    protected function createDriver(bool $realDriver = false): DriverInterface
    {
        if ($realDriver) {
            return new SynchronousDriver($this->getLoop(), $this->getWorker());
        }

        return $this->createMock(DriverInterface::class);
    }

    protected function createLoop(): LoopInterface
    {
        return new SignalLoop();
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
            'not-supported' => function () {
                throw new PayloadNotSupportedException($this->driver, new RetryablePayload());
            },
        ];
    }

    protected function needsRealDriver(): bool
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
