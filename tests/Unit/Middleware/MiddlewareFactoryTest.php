<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareFactory;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\InvalidController;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\TestMiddleware;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateCallableFromArray(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware([TestCallableMiddleware::class, 'index']);
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
            static function (): Request {
                return new Request(new Message('test', 'test data'), new FakeAdapter());
            }
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
            static function (): MiddlewareInterface {
                return new TestMiddleware();
            }
        );
        self::assertSame(
            'New middleware test data',
            $middleware->process(
                $this->getRequest(),
                $this->createMock(MessageHandlerInterface::class)
            )->getMessage()->getData()
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(TestMiddleware::class);

        self::assertSame(
            'New middleware test data',
            $middleware->process(
                $this->getRequest(),
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware([TestCallableMiddleware::class, 'index']);
        $request = $this->getRequest();

        self::assertSame(
            'New test data',
            $middleware->process(
                $request,
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function invalidMiddlewareDefinitionProvider(): array
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

    /**
     * @dataProvider invalidMiddlewareDefinitionProvider
     */
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
        return new Request(new Message('handler', 'data'), new FakeAdapter());
    }
}
