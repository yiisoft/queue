<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\HandlerEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactory;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Tests\Support\NullMessageHandler;
use Yiisoft\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;

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
    protected ?EventDispatcherInterface $eventDispatcher = null;
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
        return $this->worker ??= new Worker(
            new NullLogger(),
            $this->createEventDispatcher(),
            $this->getMiddlewareDispatcher(),
            $this->getMiddlewareDispatcher(),
        );
    }

    protected function createEventDispatcher(): EventDispatcherInterface
    {
        $container = $this->getContainer();
        $listeners = new ListenerCollection();
        $listeners = $listeners->add(function (Message $message) use ($container) {
            $handler = HandlerEnvelope::fromMessage($message)->getHandler();

            if ($handler) {
                return $container->get($handler)->handle($message);
            }
            throw new \RuntimeException('Handler not found ' . print_r($message, true));
        });
        return $this->eventDispatcher ??= new Dispatcher(
            new Provider(
                $listeners
            )
        );
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
            $this->getMiddlewareDispatcher(),
        );
    }

    protected function createAdapter(bool $realAdapter): AdapterInterface
    {
        if ($realAdapter) {
            return new SynchronousAdapter();
        }

        return $this->createMock(AdapterInterface::class);
    }

    protected function createLoop(): LoopInterface
    {
        return new SimpleLoop();
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

    protected function getMiddlewareDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory(
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
            ),
        );
    }
}
