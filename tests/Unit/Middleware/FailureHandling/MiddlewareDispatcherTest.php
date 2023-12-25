<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getFailureHandlingRequest();

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [
                    static function (FailureHandlingRequest $request): FailureHandlingRequest {
                        return $request->withMessage(new Message('test', 'New closure test data'));
                    },
                ],
            ]
        );

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New closure test data', $request->getMessage()->getData());
    }

    public function testArrayMiddlewareCallableDefinition(): void
    {
        $request = $this->getFailureHandlingRequest();
        $container = $this->createContainer(
            [
                TestCallableMiddleware::class => new TestCallableMiddleware(),
            ]
        );
        $dispatcher = $this
            ->createDispatcher($container)
            ->withMiddlewares(
                [
                    FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [[TestCallableMiddleware::class, 'index']],
                ]
            );
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New test data', $request->getMessage()->getData());
    }

    public function testFactoryArrayDefinition(): void
    {
        $request = $this->getFailureHandlingRequest();
        $container = $this->createContainer();
        $definition = [
            'class' => TestMiddleware::class,
            '__construct()' => ['message' => 'New test data from the definition'],
        ];
        $dispatcher = $this->createDispatcher($container)->withMiddlewares([FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [$definition]]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New test data from the definition', $request->getMessage()->getData());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $request = $this->getFailureHandlingRequest();

        $middleware1 = static function (FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest {
            $request = $request->withMessage(new Message($request->getMessage()->getHandler(), 'new test data'));

            return $handler->handleFailure($request);
        };
        $middleware2 = static function (FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest {
            $request = $request->withMessage(new Message('new handler', $request->getMessage()->getData()));

            return $handler->handleFailure($request);
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [$middleware1, $middleware2]]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('new test data', $request->getMessage()->getData());
        $this->assertSame('new handler', $request->getMessage()->getHandler());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = $this->getFailureHandlingRequest();

        $middleware1 = static function (FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest {
            return $request->withMessage(new Message($request->getMessage()->getHandler(), 'first'));
        };
        $middleware2 = static function (FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest {
            return $request->withMessage(new Message($request->getMessage()->getHandler(), 'second'));
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [$middleware1, $middleware2]]);

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

    public function testImmutability(): void
    {
        $dispatcher = $this->createDispatcher();
        self::assertNotSame($dispatcher, $dispatcher->withMiddlewares([]));
    }

    public function testResetStackOnWithMiddlewares(): void
    {
        $request = $this->getFailureHandlingRequest();
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

        $dispatcher = $dispatcher->withMiddlewares([FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [TestMiddleware::class]]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());

        self::assertSame('New middleware test data', $request->getMessage()->getData());
    }

    private function getRequestHandler(): MessageFailureHandlerInterface
    {
        return new class () implements MessageFailureHandlerInterface {
            public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
            {
                return $request;
            }
        };
    }

    private function createDispatcher(
        ContainerInterface $container = null,
    ): FailureMiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new FakeAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new FailureMiddlewareDispatcher(new MiddlewareFactoryFailure($container, $callableFactory), []);
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getFailureHandlingRequest(): FailureHandlingRequest
    {
        return new FailureHandlingRequest(
            new Message('handler', 'data'),
            new Exception('Test exception.'),
            $this->createMock(QueueInterface::class)
        );
    }
}
