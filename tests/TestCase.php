<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
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
use Yiisoft\Yii\Queue\Tests\Support\NullMessageHandler;
use Yiisoft\Yii\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Yii\Queue\Worker\Worker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/**
 * Base Test Case.
 */
abstract class TestCase extends BaseTestCase
{
    protected ?ContainerInterface $container = null;
    protected Queue|null $queue = null;
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

    /**
     * @return Queue The same object every time
     */
    protected function getQueue(): Queue
    {
        return $this->queue ??= $this->createQueue();
    }

    /**
     * @return AdapterInterface|MockObject
     */
    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter ??= $this->createAdapter($this->needsRealAdapter());
    }

    protected function getLoop(): LoopInterface
    {
        return $this->loop ??= $this->createLoop();
    }

    protected function getWorker(): WorkerInterface
    {
        return $this->worker ??= $this->createWorker();
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container ??= $this->createContainer();
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

    protected function createAdapter(bool $realAdapter): AdapterInterface
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
            [],
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
        return [
            NullMessageHandler::class => new NullMessageHandler(),
            StackMessageHandler::class => new StackMessageHandler(),
        ];
    }

    protected function setEventHandlers(callable ...$handlers): void
    {
        $this->eventHandlers = $handlers;
    }

    protected function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }

    protected function needsRealAdapter(): bool
    {
        return false;
    }

    protected function getPushMiddlewareDispatcher(): PushMiddlewareDispatcher
    {
        return new PushMiddlewareDispatcher(
            new MiddlewareFactoryPush(
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
            ),
        );
    }

    protected function getConsumeMiddlewareDispatcher(): ConsumeMiddlewareDispatcher
    {
        return new ConsumeMiddlewareDispatcher(
            new MiddlewareFactoryConsume(
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
            ),
        );
    }

    protected function getFailureMiddlewareDispatcher(): FailureMiddlewareDispatcher
    {
        return new FailureMiddlewareDispatcher(
            new MiddlewareFactoryFailure(
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
            ),
            [],
        );
    }
}
