<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\Integration\Support\ConsumeMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\TestCallableMiddleware;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\MiddlewareFactory;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\InvalidController;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromClassString(): void
    {
        $container = $this->getContainer([ConsumeMiddleware::class => new ConsumeMiddleware('stage1')]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(ConsumeMiddleware::class);
        self::assertInstanceOf(ConsumeMiddleware::class, $middleware);
    }

    public function testCreateFromAliasString(): void
    {
        $container = $this->getContainer(['test' => new ConsumeMiddleware('stage1')]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware('test');
        self::assertInstanceOf(ConsumeMiddleware::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            [TestCallableMiddleware::class, 'index']
        );
        self::assertSame(
            'New test data',
            $middleware->process(
                $this->getRequest(),
                $this->createMock(MessageHandlerInterface::class)
            )->getMessage()->getData(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            fn (): Request => new Request(
                new Message('test data'),
                $this->createMock(QueueInterface::class),
            )
        );
        self::assertSame(
            'test data',
            $middleware->process(
                $this->getRequest(),
                $this->createMock(MessageHandlerInterface::class)
            )->getMessage()->getData()
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            static fn (): MiddlewareInterface => new ConsumeMiddleware('stage1')
        );

        $handler = $this->createMock(MessageHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturnCallback(
            static fn (Request $request): Request => $request->withMessage(
                new Message('New middleware test data')
            )
        );

        self::assertSame(
            'New middleware test data',
            $middleware->process(
                $this->getRequest(),
                $handler
            )->getMessage()->getData()
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([ConsumeMiddleware::class => new ConsumeMiddleware('stage1')]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(ConsumeMiddleware::class);

        self::assertSame(
            ['data', 'stage1'],
            $middleware->process(
                $this->getRequest(),
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            [TestCallableMiddleware::class, 'index']
        );
        $request = $this->getRequest();

        self::assertSame(
            'New test data',
            $middleware->process(
                $request,
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            static fn () => 42
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->process(
            $this->getRequest(),
            $this->createMock(MessageHandlerInterface::class)
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
        $this->getMiddlewareFactory()->createMiddleware($definition);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            [InvalidController::class, 'index']
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->process(
            $this->getRequest(),
            $this->createMock(MessageHandlerInterface::class)
        );
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryInterface
    {
        $container ??= $this->getContainer([AdapterInterface::class => new FakeAdapter()]);

        return new MiddlewareFactory($container, new CallableFactory($container));
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
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

    private function getRequest(): Request
    {
        return new Request(
            new Message(['data']),
            $this->createMock(QueueInterface::class)
        );
    }
}
