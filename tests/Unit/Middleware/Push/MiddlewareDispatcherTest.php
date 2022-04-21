<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Push\Event\AfterMiddleware;
use Yiisoft\Yii\Queue\Middleware\Push\Event\BeforeMiddleware;
use Yiisoft\Yii\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support\FailMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support\TestCallableMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getPushRequest();

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                static function (PushRequest $request, AdapterInterface $adapter): PushRequest {
                    return $request
                        ->withMessage(new Message('test', 'New closure test data'))
                        ->withAdapter($adapter->withChannel('closure-channel'));
                },
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

    public function testArrayMiddlewareCall(): void
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

        $middleware1 = static function (PushRequest $request, MessageHandlerPushInterface $handler): PushRequest {
            $request = $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'first'));

            return $request;
        };
        $middleware2 = static function (PushRequest $request, MessageHandlerPushInterface $handler): PushRequest {
            $request = $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'second'));

            return $request;
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('first', $request->getMessage()->getData());
    }

    public function testEventsAreDispatched(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $request = $this->getPushRequest();

        $middleware1 = static function (PushRequest $request, MessageHandlerPushInterface $handler): PushRequest {
            return $handler->handlePush($request);
        };
        $middleware2 = static function (PushRequest $request): PushRequest {
            return $request;
        };

        $dispatcher = $this->createDispatcher(null, $eventDispatcher)->withMiddlewares([$middleware1, $middleware2]);
        $dispatcher->dispatch($request, $this->getRequestHandler());

        $this->assertEquals(
            [
                BeforeMiddleware::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterMiddleware::class,
            ],
            $eventDispatcher->getEventClasses()
        );
    }

    public function testEventsAreDispatchedWhenMiddlewareFailedWithException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Middleware failed.');

        $request = $this->getPushRequest();
        $eventDispatcher = new SimpleEventDispatcher();
        $middleware = static fn() => new FailMiddleware();
        $dispatcher = $this->createDispatcher(null, $eventDispatcher)->withMiddlewares([$middleware]);

        try {
            $dispatcher->dispatch($request, $this->getRequestHandler());
        } finally {
            $this->assertEquals(
                [
                    BeforeMiddleware::class,
                    AfterMiddleware::class,
                ],
                $eventDispatcher->getEventClasses()
            );
        }
    }

    public function dataHasMiddlewares(): array
    {
        return [
            [[], false],
            [[[TestCallableMiddleware::class, 'index']], true],
        ];
    }

    /**
     * @dataProvider dataHasMiddlewares
     */
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
        ?EventDispatcherInterface $eventDispatcher = null
    ): PushMiddlewareDispatcher {
        $container = $container ?? $this->createContainer([AdapterInterface::class => new FakeAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new PushMiddlewareDispatcher(
            new MiddlewareFactoryPush($container, $callableFactory),
            $eventDispatcher
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
