<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $message = $this->getMessage();

        $dispatcher = $this->createDispatcher(middlewareDefinitions: [
            static function (MessageInterface $message, PushHandlerInterface $handler): MessageInterface {
                return new GenericMessage('test', 'New closure test data');
            },
        ]);

        $result = $dispatcher->dispatch($message);
        $this->assertSame('New closure test data', $result->getPayload());
    }

    public function testArrayMiddlewareCallableDefinition(): void
    {
        $message = $this->getMessage();
        $container = $this->createContainer(
            [
                TestCallableMiddleware::class => new TestCallableMiddleware(),
            ],
        );
        $dispatcher = $this->createDispatcher($container, [[TestCallableMiddleware::class, 'index']]);
        $result = $dispatcher->dispatch($message);
        $this->assertSame('New test data', $result->getPayload());
    }

    public function testFactoryArrayDefinition(): void
    {
        $message = $this->getMessage();
        $container = $this->createContainer();
        $definition = [
            'class' => TestMiddleware::class,
            '__construct()' => ['message' => 'New test data from the definition'],
        ];
        $dispatcher = $this->createDispatcher($container, [$definition]);
        $result = $dispatcher->dispatch($message);
        $this->assertSame('New test data from the definition', $result->getPayload());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $message = $this->getMessage();

        $middleware1 = static function (MessageInterface $message, PushHandlerInterface $handler): MessageInterface {
            return $handler->handlePush(new GenericMessage($message->getType(), 'new test data'));
        };
        $middleware2 = static function (MessageInterface $message, PushHandlerInterface $handler): MessageInterface {
            return $handler->handlePush($message);
        };

        $dispatcher = $this->createDispatcher(middlewareDefinitions: [$middleware1, $middleware2]);

        $result = $dispatcher->dispatch($message);
        $this->assertSame('new test data', $result->getPayload());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $message = $this->getMessage();

        $middleware1 = static fn(MessageInterface $message, PushHandlerInterface $handler): MessageInterface => new GenericMessage($message->getType(), 'first');
        $middleware2 = static fn(MessageInterface $message, PushHandlerInterface $handler): MessageInterface => new GenericMessage($message->getType(), 'second');

        $dispatcher = $this->createDispatcher(middlewareDefinitions: [$middleware1, $middleware2]);

        $result = $dispatcher->dispatch($message);
        $this->assertSame('first', $result->getPayload());
    }

    private function createDispatcher(
        ?ContainerInterface $container = null,
        array $middlewareDefinitions = [],
    ): PushMiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new InMemoryAdapter()]);

        return new PushMiddlewareDispatcher(
            new PushMiddlewareFactory($container),
            $middlewareDefinitions,
            new class implements PushHandlerInterface {
                public function handlePush(MessageInterface $message): MessageInterface
                {
                    return $message;
                }
            },
        );
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getMessage(): MessageInterface
    {
        return new GenericMessage('handler', 'data');
    }
}
