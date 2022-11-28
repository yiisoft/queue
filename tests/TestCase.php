<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Factory\Factory;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SimpleLoop;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Worker\Worker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/**
 * Base Test Case.
 */
abstract class TestCase extends BaseTestCase
{
    protected ?ContainerInterface $container = null;
    protected QueueInterface|MockObject|null $queue = null;
    protected ?AdapterInterface $adapter = null;
    protected ?LoopInterface $loop = null;
    protected ?WorkerInterface $worker = null;
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
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger(),
            $this->getPushMiddlewareDispatcher(),
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
        return new SimpleLoop();
    }

    protected function createWorker(): WorkerInterface
    {
        return new Worker(
            $this->getMessageHandlers(),
            new NullLogger(),
            new Injector($this->getContainer()),
            $this->getContainer(),
            $this->getConsumeMiddlewareDispatcher(),
            $this->getFailureMiddlewareDispatcher(),
        );
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

    protected function getPushMiddlewareDispatcher()
    {
        return new PushMiddlewareDispatcher(
            new MiddlewareFactoryPush(
                $this->getContainer(),
                new Factory($this->getContainer()),
                new CallableFactory($this->getContainer()),
            ),
        );
    }

    protected function getConsumeMiddlewareDispatcher()
    {
        return new ConsumeMiddlewareDispatcher(
            new MiddlewareFactoryConsume(
                $this->getContainer(),
                new Factory($this->getContainer()),
                new CallableFactory($this->getContainer()),
            ),
        );
    }

    protected function getFailureMiddlewareDispatcher(): FailureMiddlewareDispatcher
    {
        return new FailureMiddlewareDispatcher(
            new MiddlewareFactoryFailure(
                $this->getContainer(),
                $this->getFactory(),
                new CallableFactory($this->getContainer()),
            ),
            [],
        );
    }

    protected function getFactory(): Factory
    {
        return new Factory($this->getContainer());
    }
}
