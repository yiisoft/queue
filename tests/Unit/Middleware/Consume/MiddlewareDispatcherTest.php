<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\ConsumeHandlerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestMiddleware;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testCallableMiddlewareCalled(): void
    {
        $request = $this->getConsumeRequest();

        $dispatcher = $this->createDispatcher(middlewareDefinitions: [
            static function (ConsumeRequest $request): ConsumeRequest {
                return $request->withMessage(new GenericMessage('test', 'New closure test data'))->withQueueName('other-queue');
            },
        ]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New closure test data', $request->getMessage()->getPayload());
    }

    public function testArrayMiddlewareCallableDefinition(): void
    {
        $request = $this->getConsumeRequest();
        $container = $this->createContainer(
            [
                TestCallableMiddleware::class => new TestCallableMiddleware(),
            ],
        );
        $dispatcher = $this->createDispatcher($container, [[TestCallableMiddleware::class, 'index']]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New test data', $request->getMessage()->getPayload());
    }

    public function testFactoryArrayDefinition(): void
    {
        $request = $this->getConsumeRequest();
        $container = $this->createContainer();
        $definition = [
            'class' => TestMiddleware::class,
            '__construct()' => ['message' => 'New test data from the definition'],
        ];
        $dispatcher = $this->createDispatcher($container, [$definition]);
        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('New test data from the definition', $request->getMessage()->getPayload());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $request = $this->getConsumeRequest();

        $middleware1 = static function (ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest {
            $request = $request->withMessage(new GenericMessage($request->getMessage()->getType(), 'new test data'));

            return $handler->handleConsume($request);
        };
        $middleware2 = static function (ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest {
            $request = $request->withMessage(new GenericMessage('new handler', $request->getMessage()->getPayload()));

            return $handler->handleConsume($request);
        };

        $dispatcher = $this->createDispatcher(middlewareDefinitions: [$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('new test data', $request->getMessage()->getPayload());
        $this->assertSame('new handler', $request->getMessage()->getType());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = $this->getConsumeRequest();

        $middleware1 = static function (ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest {
            return $request->withMessage(new GenericMessage($request->getMessage()->getType(), 'first'));
        };
        $middleware2 = static function (ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest {
            return $request->withMessage(new GenericMessage($request->getMessage()->getType(), 'second'));
        };

        $dispatcher = $this->createDispatcher(middlewareDefinitions: [$middleware1, $middleware2]);

        $request = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame('first', $request->getMessage()->getPayload());
    }

    private function getRequestHandler(): ConsumeHandlerInterface
    {
        return new class implements ConsumeHandlerInterface {
            public function handleConsume(ConsumeRequest $request): ConsumeRequest
            {
                return $request;
            }
        };
    }

    private function createDispatcher(
        ?ContainerInterface $container = null,
        array $middlewareDefinitions = [],
    ): ConsumeMiddlewareDispatcher {
        $container ??= $this->createContainer([AdapterInterface::class => new InMemoryAdapter()]);

        return new ConsumeMiddlewareDispatcher(new ConsumeMiddlewareFactory($container), ...$middlewareDefinitions);
    }

    private function createContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getConsumeRequest(): ConsumeRequest
    {
        return new ConsumeRequest(
            new GenericMessage('handler', 'data'),
            'test-queue',
        );
    }
}
