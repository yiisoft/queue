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
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\CallableObjectMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\InvalidController;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\StringCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Push\Support\TestMiddleware;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateCallableFromArray(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware([TestCallableMiddleware::class, 'index']);
        self::assertSame(
            'New test data',
            $middleware->processPush(
                $this->getMessage(),
                $this->createMock(PushHandlerInterface::class),
            )->getData(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            static function (): MessageInterface {
                return new GenericMessage('test', 'test data');
            },
        );
        self::assertSame(
            'test data',
            $middleware->processPush(
                $this->getMessage(),
                $this->createMock(PushHandlerInterface::class),
            )->getData(),
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            static function (): PushMiddlewareInterface {
                return new TestMiddleware();
            },
        );
        self::assertSame(
            'New middleware test data',
            $middleware->processPush(
                $this->getMessage(),
                $this->createMock(PushHandlerInterface::class),
            )->getData(),
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(TestMiddleware::class);

        self::assertSame(
            'New middleware test data',
            $middleware->processPush(
                $this->getMessage(),
                $this->getRequestHandler(),
            )->getData(),
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware([TestCallableMiddleware::class, 'index']);

        self::assertSame(
            'New test data',
            $middleware->processPush(
                $this->getMessage(),
                $this->getRequestHandler(),
            )->getData(),
        );
    }

    public function testCreateFromStringCallable(): void
    {
        $middleware = $this->getMiddlewareFactory()->createPushMiddleware(
            StringCallableMiddleware::class . '::handle',
        );
        self::assertSame(
            'String callable data',
            $middleware->processPush(
                $this->getMessage(),
                $this->createMock(PushHandlerInterface::class),
            )->getData(),
        );
    }

    public function testCreateFromCallableObject(): void
    {
        $middleware = $this->getMiddlewareFactory()->createPushMiddleware(
            new CallableObjectMiddleware(),
        );
        self::assertSame(
            'Callable object data',
            $middleware->processPush(
                $this->getMessage(),
                $this->createMock(PushHandlerInterface::class),
            )->getData(),
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
            'array wrong class' => [['class' => TestCallableMiddleware::class]],
        ];
    }

    #[DataProvider('invalidMiddlewareDefinitionProvider')]
    public function testInvalidMiddleware(mixed $definition): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $this->getMiddlewareFactory()->createPushMiddleware($definition);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            [InvalidController::class, 'index'],
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processPush(
            $this->getMessage(),
            $this->createMock(PushHandlerInterface::class),
        );
    }

    private function getMiddlewareFactory(?ContainerInterface $container = null): PushMiddlewareFactoryInterface
    {
        $container ??= $this->getContainer([AdapterInterface::class => new InMemoryAdapter()]);

        return new PushMiddlewareFactory($container, new CallableFactory($container));
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getRequestHandler(): PushHandlerInterface
    {
        return new class implements PushHandlerInterface {
            public function handlePush(MessageInterface $message): MessageInterface
            {
                return $message;
            }
        };
    }

    private function getMessage(): MessageInterface
    {
        return new GenericMessage('handler', 'data');
    }
}
