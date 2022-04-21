<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\Event\AfterConsumeMiddleware;
use Yiisoft\Yii\Queue\Middleware\Consume\Event\BeforeConsumeMiddleware;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\FailMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getConsumeRequest();

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                static function (ConsumeRequest $request): ConsumeRequest {
                    return $request->withMessage(new Message('test', 'New closure test data'));
                },
            ]
        );

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New closure test data', $request->getMessage()->getData());
    }

    public function testArrayMiddlewareCall(): void
    {
        $request = $this->getConsumeRequest();
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
        $request = $this->getConsumeRequest();

        $middleware1 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            $request = $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'new test data'));

            return $handler->handleConsume($request);
        };
        $middleware2 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            $request = $request->withMessage(new Message('new handler', $request->getMessage()->getData()));

            return $handler->handleConsume($request);
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('new test data', $request->getMessage()->getData());
        $this->assertSame('new handler', $request->getMessage()->getHandlerName());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = $this->getConsumeRequest();

        $middleware1 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            return $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'first'));
        };
        $middleware2 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            return $request->withMessage(new Message($request->getMessage()->getHandlerName(), 'second'));
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('first', $request->getMessage()->getData());
    }

    public function testEventsAreDispatched(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $request = $this->getConsumeRequest();

        $middleware1 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            return $handler->handleConsume($request);
        };
        $middleware2 = static function (ConsumeRequest $request): ConsumeRequest {
            return $request;
        };

        $dispatcher = $this->createDispatcher(null, $eventDispatcher)->withMiddlewares([$middleware1, $middleware2]);
        $dispatcher->dispatch($request, $this->getRequestHandler());

        $this->assertEquals(
            [
                BeforeConsumeMiddleware::class,
                BeforeConsumeMiddleware::class,
                AfterConsumeMiddleware::class,
                AfterConsumeMiddleware::class,
            ],
            $eventDispatcher->getEventClasses()
        );
    }

    public function testEventsAreDispatchedWhenMiddlewareFailedWithException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Middleware failed.');

        $request = $this->getConsumeRequest();
        $eventDispatcher = new SimpleEventDispatcher();
        $middleware = static fn () => new FailMiddleware();
        $dispatcher = $this->createDispatcher(null, $eventDispatcher)->withMiddlewares([$middleware]);

        try {
            $dispatcher->dispatch($request, $this->getRequestHandler());
        } finally {
            $this->assertEquals(
                [
                    BeforeConsumeMiddleware::class,
                    AfterConsumeMiddleware::class,
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
        $request = $this->getConsumeRequest();
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

    private function getRequestHandler(): MessageHandlerConsumeInterface
    {
        return new class () implements MessageHandlerConsumeInterface {
            public function handleConsume(ConsumeRequest $request): ConsumeRequest
            {
                return $request;
            }
        };
    }

    private function createDispatcher(
        ContainerInterface $container = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ): ConsumeMiddlewareDispatcher {
        $container = $container ?? $this->createContainer([AdapterInterface::class => new FakeAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new ConsumeMiddlewareDispatcher(
            new MiddlewareFactoryConsume($container, $callableFactory),
            $eventDispatcher
        );
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getConsumeRequest(): ConsumeRequest
    {
        return new ConsumeRequest(
            new Message('handler', 'data'),
            $this->createMock(QueueInterface::class)
        );
    }
}
