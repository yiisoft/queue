<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\ConsumeHandlerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\CallableObjectMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\InvalidController;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\StringCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestMiddleware;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromClassString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateFromAliasString(): void
    {
        $container = $this->getContainer(['test' => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware('test');
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            [TestCallableMiddleware::class, 'index'],
        );
        self::assertSame(
            'New test data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(ConsumeHandlerInterface::class),
            )->getMessage()->getPayload(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            fn(): ConsumeRequest => new ConsumeRequest(
                new GenericMessage('test', 'test data'),
                $this->createMock(QueueInterface::class),
            ),
        );
        self::assertSame(
            'test data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(ConsumeHandlerInterface::class),
            )->getMessage()->getPayload(),
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            static fn(): ConsumeMiddlewareInterface => new TestMiddleware(),
        );
        self::assertSame(
            'New middleware test data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(ConsumeHandlerInterface::class),
            )->getMessage()->getPayload(),
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(TestMiddleware::class);

        self::assertSame(
            'New middleware test data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->getRequestHandler(),
            )->getMessage()->getPayload(),
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            [TestCallableMiddleware::class, 'index'],
        );
        $request = $this->getConsumeRequest();

        self::assertSame(
            'New test data',
            $middleware->processConsume(
                $request,
                $this->getRequestHandler(),
            )->getMessage()->getPayload(),
        );
    }

    public function testCreateFromStringCallable(): void
    {
        $middleware = $this->getMiddlewareFactory()->createConsumeMiddleware(
            StringCallableMiddleware::class . '::handle',
        );
        self::assertSame(
            'String callable data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(ConsumeHandlerInterface::class),
            )->getMessage()->getPayload(),
        );
    }

    public function testCreateFromCallableObject(): void
    {
        $middleware = $this->getMiddlewareFactory()->createConsumeMiddleware(
            new CallableObjectMiddleware(),
        );
        self::assertSame(
            'Callable object data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(ConsumeHandlerInterface::class),
            )->getMessage()->getPayload(),
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            static fn() => 42,
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processConsume(
            $this->getConsumeRequest(),
            $this->createMock(ConsumeHandlerInterface::class),
        );
    }

    public static function invalidMiddlewareDefinitionProvider(): array
    {
        return [
            'wrong string' => ['test'],
            'wrong class' => [TestCallableMiddleware::class],
            'wrong array size' => [['test']],
            'array not a class' => [['class', 'test']],
            'wrong array type' => [['class' => TestCallableMiddleware::class, 'index']],
            'wrong array with int items' => [[7, 42]],
            'array with wrong method name' => [[TestCallableMiddleware::class, 'notExists']],
            'array wrong class' => [['class' => InvalidController::class]],
        ];
    }

    #[DataProvider('invalidMiddlewareDefinitionProvider')]
    public function testInvalidMiddleware(mixed $definition): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->createConsumeMiddleware($definition);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            [InvalidController::class, 'index'],
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processConsume(
            $this->getConsumeRequest(),
            $this->createMock(ConsumeHandlerInterface::class),
        );
    }

    private function getMiddlewareFactory(?ContainerInterface $container = null): ConsumeMiddlewareFactoryInterface
    {
        $container ??= $this->getContainer([AdapterInterface::class => new InMemoryAdapter()]);

        return new ConsumeMiddlewareFactory($container, new CallableFactory($container));
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
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

    private function getConsumeRequest(): ConsumeRequest
    {
        return new ConsumeRequest(
            new GenericMessage('handler', 'data'),
            $this->createMock(QueueInterface::class),
        );
    }
}
