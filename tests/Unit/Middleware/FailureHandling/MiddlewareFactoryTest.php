<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareFactory;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support\InvalidController;
use Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware;
use Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestMiddleware;

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
                $this->getConsumeRequest(),
                $this->createMock(MessageHandlerInterface::class)
            )->getMessage()->getData(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            function (): FailureHandlingRequest {
                return new FailureHandlingRequest(
                    new Message('test data'),
                    new RuntimeException('test exception'),
                );
            }
        );
        self::assertSame(
            'test data',
            $middleware->process(
                $this->getConsumeRequest(),
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
                $this->getConsumeRequest(),
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
                $this->getConsumeRequest(),
                $this->getRequestHandler(new RuntimeException())
            )->getMessage()->getData()
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createMiddleware(
            [TestCallableMiddleware::class, 'index']
        );
        $request = $this->getConsumeRequest();

        self::assertSame(
            'New test data',
            $middleware->process(
                $request,
                $this->getRequestHandler(new RuntimeException())
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
            'array wrong class' => [['class' => InvalidController::class]],
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
            $this->getConsumeRequest(),
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

    private function getRequestHandler(Throwable $e): MessageHandlerInterface
    {
        return new class ($e) implements MessageHandlerInterface {
            public function __construct(private Throwable $e)
            {
            }

            public function handle(Request $request): Request
            {
                throw $this->e;
            }
        };
    }

    private function getConsumeRequest(): Request
    {
        return new Request(
            new Message('data'),
            $this->createMock(AdapterInterface::class)
        );
    }
}
