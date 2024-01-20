<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getPushRequest();

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                static fn (PushRequest $request, AdapterInterface $adapter): PushRequest => $request
                    ->withMessage(new Message('test', 'New closure test data'))
                    ->withAdapter($adapter->withChannel('closure-channel')),
            ]
        );

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New closure test data', $request->getMessage()->getData());
        /**
         * @psalm-suppress NoInterfaceProperties
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $this->assertSame('closure-channel', $request->getAdapter()->channel);
    }

    public function testArrayMiddlewareCallableDefinition(): void
    {
        $request = $this->getPushRequest();
        $container = $this->createContainer(
            [
                TestCallableMiddleware::class => new TestCallableMiddleware(),
            ]
        );
        $dispatcher = $this->createDispatcher($container)->withMiddlewares([[TestCallableMiddleware::class, 'index']]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New test data', $request->getMessage()->getData());
    }

    public function testFactoryArrayDefinition(): void
    {
        $request = $this->getPushRequest();
        $container = $this->createContainer();
        $definition = [
            'class' => TestMiddleware::class,
            '__construct()' => ['message' => 'New test data from the definition'],
        ];
        $dispatcher = $this->createDispatcher($container)->withMiddlewares([$definition]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New test data from the definition', $request->getMessage()->getData());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $request = $this->getPushRequest();

        $middleware1 = static function (PushRequest $request, MessageHandlerPushInterface $handler): PushRequest {
            $request = $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'new test data'));

            return $handler->handlePush($request);
        };
        $middleware2 = static function (PushRequest $request, MessageHandlerPushInterface $handler): PushRequest {
            /**
             * @noinspection NullPointerExceptionInspection
             *
             * @psalm-suppress PossiblyNullReference
             */
            $request = $request->withAdapter($request->getAdapter()->withChannel('new channel'));

            return $handler->handlePush($request);
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('new test data', $request->getMessage()->getData());
        /**
         * @psalm-suppress NoInterfaceProperties
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $this->assertSame('new channel', $request->getAdapter()->channel);
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = $this->getPushRequest();

        $middleware1 = static fn (PushRequest $request, MessageHandlerPushInterface $handler): PushRequest => $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'first'));
        $middleware2 = static fn (PushRequest $request, MessageHandlerPushInterface $handler): PushRequest => $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'second'));

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('first', $request->getMessage()->getData());
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
            $this->createDispatcher()->withMiddlewares($definitions)->hasMiddlewares()
        );
    }

    public function testImmutability(): void
    {
        $dispatcher = $this->createDispatcher();
        self::assertNotSame($dispatcher, $dispatcher->withMiddlewares([]));
    }

    public function testResetStackOnWithMiddlewares(): void
    {
        $request = $this->getPushRequest();
        $container = $this->createContainer(
            [
                TestCallableMiddleware::class => new TestCallableMiddleware(),
                TestMiddleware::class => new TestMiddleware(),
            ]
        );

        $dispatcher = $this
            ->createDispatcher($container)
            ->withMiddlewares([[TestCallableMiddleware::class, 'index']]);
        $dispatcher->dispatch($request, $this->getRequestHandler());

        $dispatcher = $dispatcher->withMiddlewares([TestMiddleware::class]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());

        self::assertSame('New middleware test data', $request->getMessage()->getData());
    }

    private function getRequestHandler(): MessageHandlerPushInterface
    {
        return new class () implements MessageHandlerPushInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                return $request;
            }
        };
    }

    private function createDispatcher(
        ContainerInterface $container = null,
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

    private function getPushRequest(): PushRequest
    {
        return new PushRequest(new Message('handler', 'data'), new FakeAdapter());
    }
}
