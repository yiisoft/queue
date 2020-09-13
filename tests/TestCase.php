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
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\ContainerConfigurator;
use Yiisoft\Yii\Queue\Worker\Worker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/**
 * Base Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends BaseTestCase
{
    protected ?ContainerInterface $container;
    protected ?Queue $queue = null;
    protected ?DriverInterface $driver = null;
    protected ?LoopInterface $loop = null;
    protected ?WorkerInterface $worker = null;
    protected ?EventDispatcherInterface $dispatcher = null;

    protected function setUp(): void
    {
    }

    protected function getQueue(): Queue
    {
        if ($this->queue === null) {
            $this->queue = $this->createQueue();
        }

        return $this->queue;
    }

    /**
     * @param bool $driverMock
     *
     * @return DriverInterface|MockObject
     */
    protected function getDriver(bool $driverMock = true): DriverInterface
    {
        if ($this->driver === null) {
            $this->driver = $this->createDriver($driverMock);
        } elseif ($driverMock && !$this->driver instanceof MockObject) {
            $this->driver = $this->createDriver($driverMock);
        } elseif ($driverMock === false && $this->driver instanceof MockObject) {
            $this->driver = $this->createDriver($driverMock);
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

    protected function getEventDispatcher(): EventDispatcherInterface
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

    protected function createQueue(bool $driverMock = true): Queue
    {
        return new Queue(
            $this->getDriver($driverMock),
            $this->getEventDispatcher(),
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger()
        );
    }

    protected function createDriver(): DriverInterface
    {
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

    protected function createEventDispatcher(): EventDispatcherInterface
    {
        return new SimpleEventDispatcher($this->getEventHandlers());
    }

    protected function createContainer(): ContainerInterface
    {
        return new SimpleContainer($this->getContainerDefinitions());
    }

    protected function getContainerDefinitions(): array
    {
        return [];
    }

    protected function getEventHandlers(): array
    {
        return [];
    }

    protected function getMessageHandlers(): array
    {
        return [];
    }
}
