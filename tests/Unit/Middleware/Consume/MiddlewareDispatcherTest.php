<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getConsumeRequest();
        $queue = $this->createMock(QueueInterface::class);

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                static function (ConsumeRequest $request) use ($queue): ConsumeRequest {
                    return $request->withMessage(new Message('test', 'New closure test data'))->withQueue($queue);
                },
            ]
        );

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New closure test data', $request->getMessage()->getData());
    }

    public function testArrayMiddlewareCallableDefinition(): void
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

    public function testFactoryArrayDefinition(): void
    {
        $request = $this->getConsumeRequest();
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
        $request = $this->getConsumeRequest();

        $middleware1 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            $request = $request->withMessage(new Message($request->getMessage()->getHandler(), 'new test data'));

            return $handler->handleConsume($request);
        };
        $middleware2 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            $request = $request->withMessage(new Message('new handler', $request->getMessage()->getData()));

            return $handler->handleConsume($request);
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('new test data', $request->getMessage()->getData());
        $this->assertSame('new handler', $request->getMessage()->getHandler());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = $this->getConsumeRequest();

        $middleware1 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            return $request->withMessage(new Message($request->getMessage()->getHandler(), 'first'));
        };
        $middleware2 = static function (ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest {
            return $request->withMessage(new Message($request->getMessage()->getHandler(), 'second'));
        };

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
    ): ConsumeMiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new FakeAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new ConsumeMiddlewareDispatcher(
            new MiddlewareFactoryConsume($container, $callableFactory),
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
