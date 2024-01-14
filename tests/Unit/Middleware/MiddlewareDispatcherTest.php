<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Queue\Tests\App\FakeQueue;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareFactory;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getRequest();

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                static fn (Request $request): Request => $request
                    ->withMessage(new Message('New closure test data'))
                    ->withQueue(
                        $request->getQueue()->getAdapter() === null
                            ? $request->getQueue()
                            : $request->getQueue()->withAdapter(
                                $request->getQueue()->getAdapter()->withChannel('closure-channel')
                            )
                    ),
            ]
        );

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New closure test data', $request->getMessage()->getData());
        /**
         * @psalm-suppress NoInterfaceProperties
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $this->assertSame('closure-channel', $request->getQueue()->getAdapter()->channel);
    }

    public function testArrayMiddlewareCallableDefinition(): void
    {
        $request = $this->getRequest();
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
        $request = $this->getRequest();
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
        $middleware1 = static function (Request $request, MessageHandlerInterface $handler): Request {
            $request = $request->withMessage($request->getMessage()->withData('new test data'));

            return $handler->handle($request);
        };
        $middleware2 = static function (Request $request, MessageHandlerInterface $handler): Request {
            /**
             * @noinspection NullPointerExceptionInspection
             *
             * @psalm-suppress PossiblyNullReference
             */
            $queue = $request->getQueue();
            if ($queue !== null && $queue->getAdapter() !== null) {
                $request = $request->withQueue(
                    $queue->withAdapter(
                        $queue->getAdapter()->withChannel('new channel')
                    )
                );
            }

            return $handler->handle($request);
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $this->getRequest();
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('new test data', $request->getMessage()->getData());
        /**
         * @psalm-suppress NoInterfaceProperties
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $this->assertNotNull($request->getQueue());
        $this->assertNotNull($request->getQueue()->getAdapter());
        $this->assertInstanceOf(FakeAdapter::class, $request->getQueue()->getAdapter());
        $this->assertSame('new channel', $request->getQueue()->getAdapter()->channel);
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = $this->getRequest();

        $middleware1 = static fn (Request $request, MessageHandlerInterface $handler): Request => $request->withMessage($request->getMessage()->withData('first'));
        $middleware2 = static fn (Request $request, MessageHandlerInterface $handler): Request => $request->withMessage($request->getMessage()->withData('second'));

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('first', $request->getMessage()->getData());
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
        $request = $this->getRequest();
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

    private function getRequestHandler(): MessageHandlerInterface
    {
        return new class () implements MessageHandlerInterface {
            public function handle(Request $request): Request
            {
                return $request;
            }
        };
    }

    private function createDispatcher(
        ContainerInterface $container = null,
    ): MiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new FakeAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new MiddlewareDispatcher(
            new MiddlewareFactory($container, $callableFactory),
        );
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getRequest(): Request
    {
        $queue = new FakeQueue('chan1');
        return new Request(new Message('data'), $queue->withAdapter(new FakeAdapter()));
    }
}
