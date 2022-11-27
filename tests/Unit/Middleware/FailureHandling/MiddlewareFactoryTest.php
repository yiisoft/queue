<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Factory\Factory;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFailureFactoryInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;
use Yiisoft\Yii\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\InvalidController;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestMiddleware;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateCallableFromArray(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware([TestCallableMiddleware::class, 'index']);
        self::assertSame(
            'New test data',
            $middleware->processFailure(
                $this->getConsumeRequest(),
                $this->createMock(MessageFailureHandlerInterface::class)
            )->getMessage()->getData(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware(
            function (): FailureHandlingRequest {
                return new FailureHandlingRequest(
                    new Message('test', 'test data'),
                    new RuntimeException('test exception'),
                    $this->createMock(QueueInterface::class),
                );
            }
        );
        self::assertSame(
            'test data',
            $middleware->processFailure(
                $this->getConsumeRequest(),
                $this->createMock(MessageFailureHandlerInterface::class)
            )->getMessage()->getData()
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware(
            static function (): MiddlewareFailureInterface {
                return new TestMiddleware();
            }
        );
        self::assertSame(
            'New middleware test data',
            $middleware->processFailure(
                $this->getConsumeRequest(),
                $this->createMock(MessageFailureHandlerInterface::class)
            )->getMessage()->getData()
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware(TestMiddleware::class);

        self::assertSame(
            'New middleware test data',
            $middleware->processFailure(
                $this->getConsumeRequest(),
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware(
            [TestCallableMiddleware::class, 'index']
        );
        $request = $this->getConsumeRequest();

        self::assertSame(
            'New test data',
            $middleware->processFailure(
                $request,
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware(
            static function () {
                return 42;
            }
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processFailure(
            $this->getConsumeRequest(),
            $this->createMock(MessageFailureHandlerInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongString(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createFailureMiddleware('test');
    }

    public function testInvalidMiddlewareWithWrongClass(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createFailureMiddleware(TestCallableMiddleware::class);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->createFailureMiddleware(
            [InvalidController::class, 'index']
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processFailure(
            $this->getConsumeRequest(),
            $this->createMock(MessageFailureHandlerInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongArraySize(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createFailureMiddleware(['test']);
    }

    public function testInvalidMiddlewareWithWrongArrayClass(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createFailureMiddleware(['class', 'test']);
    }

    public function testInvalidMiddlewareWithWrongArrayType(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createFailureMiddleware(['class' => TestCallableMiddleware::class, 'index']);
    }

    public function testInvalidMiddlewareWithWrongArrayWithIntItems(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createFailureMiddleware([7, 42]);
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFailureFactoryInterface
    {
        $container = $container ?? $this->getContainer([AdapterInterface::class => new FakeAdapter()]);

        return new MiddlewareFactoryFailure($container, new Factory($container), new CallableFactory($container));
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getRequestHandler(): MessageFailureHandlerInterface
    {
        return new class () implements MessageFailureHandlerInterface {
            public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
            {
                throw $request->getException();
            }
        };
    }

    private function getConsumeRequest(): FailureHandlingRequest
    {
        return new FailureHandlingRequest(
            new Message('handler', 'data'),
            new Exception('test exception'),
            $this->createMock(QueueInterface::class)
        );
    }
}
