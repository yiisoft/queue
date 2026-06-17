<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
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

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                static function (MessageInterface $message, PushHandlerInterface $handler): MessageInterface {
                    return new GenericMessage('test', 'New closure test data');
                },
            ],
        );

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
        $dispatcher = $this->createDispatcher($container)->withMiddlewares([[TestCallableMiddleware::class, 'index']]);
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
        $dispatcher = $this->createDispatcher($container)->withMiddlewares([$definition]);
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

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $result = $dispatcher->dispatch($message);
        $this->assertSame('new test data', $result->getPayload());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $message = $this->getMessage();

        $middleware1 = static fn(MessageInterface $message, PushHandlerInterface $handler): MessageInterface => new GenericMessage($message->getType(), 'first');
        $middleware2 = static fn(MessageInterface $message, PushHandlerInterface $handler): MessageInterface => new GenericMessage($message->getType(), 'second');

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $result = $dispatcher->dispatch($message);
        $this->assertSame('first', $result->getPayload());
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
        $dispatcher->dispatch($message);

        $dispatcher = $dispatcher->withMiddlewares([TestMiddleware::class]);
        $result = $dispatcher->dispatch($message);

        self::assertSame('New middleware test data', $result->getPayload());
    }

    private function createDispatcher(
        ?ContainerInterface $container = null,
    ): PushMiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new InMemoryAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new PushMiddlewareDispatcher(
            new PushMiddlewareFactory($container, $callableFactory),
            [],
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
