<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $message = $this->getMessage();

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                static function (MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface {
                    return new Message('test', 'New closure test data');
                },
            ],
        );

        $result = $dispatcher->dispatch($message, $this->getRequestHandler());
        $this->assertSame('New closure test data', $result->getData());
    }

    public function testArrayMiddlewareCallableDefinition(): void
    {
        $message = $this->getMessage();
        $container = $this->createContainer(
            [
                TestCallableMiddleware::class => new TestCallableMiddleware(),
            ],
        );
        $dispatcher = $this->createDispatcher($container)->withMiddlewares([[TestCallableMiddleware::class, 'index']]);
        $result = $dispatcher->dispatch($message, $this->getRequestHandler());
        $this->assertSame('New test data', $result->getData());
    }

    public function testFactoryArrayDefinition(): void
    {
        $message = $this->getMessage();
        $container = $this->createContainer();
        $definition = [
            'class' => TestMiddleware::class,
            '__construct()' => ['message' => 'New test data from the definition'],
        ];
        $dispatcher = $this->createDispatcher($container)->withMiddlewares([$definition]);
        $result = $dispatcher->dispatch($message, $this->getRequestHandler());
        $this->assertSame('New test data from the definition', $result->getData());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $message = $this->getMessage();

        $middleware1 = static function (MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface {
            return $handler->handlePush(new Message($message->getType(), 'new test data'));
        };
        $middleware2 = static function (MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface {
            return $handler->handlePush($message);
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $result = $dispatcher->dispatch($message, $this->getRequestHandler());
        $this->assertSame('new test data', $result->getData());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $message = $this->getMessage();

        $middleware1 = static fn(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface => new Message($message->getType(), 'first');
        $middleware2 = static fn(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface => new Message($message->getType(), 'second');

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $result = $dispatcher->dispatch($message, $this->getRequestHandler());
        $this->assertSame('first', $result->getData());
    }

    public static function dataHasMiddlewares(): array
    {
        return [
            [[], false],
            [[[TestCallableMiddleware::class, 'index']], true],
        ];
    }

    #[DataProvider('dataHasMiddlewares')]
    public function testHasMiddlewares(array $definitions, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->createDispatcher()->withMiddlewares($definitions)->hasMiddlewares(),
        );
    }

    public function testImmutability(): void
    {
        $dispatcher = $this->createDispatcher();
        self::assertNotSame($dispatcher, $dispatcher->withMiddlewares([]));
    }

    public function testResetStackOnWithMiddlewares(): void
    {
        $message = $this->getMessage();
        $container = $this->createContainer(
            [
                TestCallableMiddleware::class => new TestCallableMiddleware(),
                TestMiddleware::class => new TestMiddleware(),
            ],
        );

        $dispatcher = $this
            ->createDispatcher($container)
            ->withMiddlewares([[TestCallableMiddleware::class, 'index']]);
        $dispatcher->dispatch($message, $this->getRequestHandler());

        $dispatcher = $dispatcher->withMiddlewares([TestMiddleware::class]);
        $result = $dispatcher->dispatch($message, $this->getRequestHandler());

        self::assertSame('New middleware test data', $result->getData());
    }

    private function getRequestHandler(): MessageHandlerPushInterface
    {
        return new class implements MessageHandlerPushInterface {
            public function handlePush(MessageInterface $message): MessageInterface
            {
                return $message;
            }
        };
    }

    private function createDispatcher(
        ?ContainerInterface $container = null,
    ): PushMiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new FakeAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new PushMiddlewareDispatcher(
            new MiddlewareFactoryPush($container, $callableFactory),
        );
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getMessage(): MessageInterface
    {
        return new Message('handler', 'data');
    }
}
