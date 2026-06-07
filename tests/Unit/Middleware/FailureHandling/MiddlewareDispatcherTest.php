<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getFailureHandlingRequest();

        $dispatcher = $this->createDispatcher()->withMiddlewares(
            [
                FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [
                    static function (FailureHandlingRequest $request): FailureHandlingRequest {
                        return $request->withMessage(new GenericMessage('test', 'New closure test data'));
                    },
                ],
            ],
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
            ],
        );
        $dispatcher = $this
            ->createDispatcher($container)
            ->withMiddlewares(
                [
                    FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [[TestCallableMiddleware::class, 'index']],
                ],
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

        $middleware1 = static function (FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest {
            $request = $request->withMessage(new GenericMessage($request->getMessage()->getType(), 'new test data'));

            return $handler->handleFailure($request);
        };
        $middleware2 = static function (FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest {
            $request = $request->withMessage(new GenericMessage('new handler', $request->getMessage()->getData()));

            return $handler->handleFailure($request);
        };

        $dispatcher = $this->createDispatcher()->withMiddlewares([FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [$middleware1, $middleware2]]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('new test data', $request->getMessage()->getData());
        $this->assertSame('new handler', $request->getMessage()->getType());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = $this->getFailureHandlingRequest();

        $middleware1 = static function (FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest {
            return $request->withMessage(new GenericMessage($request->getMessage()->getType(), 'first'));
        };
        $middleware2 = static function (FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest {
            return $request->withMessage(new GenericMessage($request->getMessage()->getType(), 'second'));
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
            ],
        );

        $dispatcher = $this
            ->createDispatcher($container)
            ->withMiddlewares([[TestCallableMiddleware::class, 'index']]);
        $dispatcher->dispatch($request, $this->getRequestHandler());

        $dispatcher = $dispatcher->withMiddlewares([FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [TestMiddleware::class]]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());

        self::assertSame('New middleware test data', $request->getMessage()->getData());
    }

    private function getRequestHandler(): FailureHandlerInterface
    {
        return new class implements FailureHandlerInterface {
            public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
            {
                return $request;
            }
        };
    }

    private function createDispatcher(
        ?ContainerInterface $container = null,
    ): FailureMiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new InMemoryAdapter()]);
        $callableFactory = new CallableFactory($container);

        return new FailureMiddlewareDispatcher(new FailureMiddlewareFactory($container, $callableFactory), []);
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getFailureHandlingRequest(): FailureHandlingRequest
    {
        return new FailureHandlingRequest(
            new GenericMessage('handler', 'data'),
            new Exception('Test exception.'),
            $this->createMock(QueueInterface::class),
        );
    }
}
