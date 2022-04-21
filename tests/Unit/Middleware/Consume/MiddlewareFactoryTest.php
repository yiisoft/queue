<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\InvalidController;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume\Support\TestMiddleware;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            [TestCallableMiddleware::class, 'index']
        );
        self::assertSame(
            'New test data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(MessageHandlerConsumeInterface::class)
            )->getMessage()->getData(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            function (): ConsumeRequest {
                return new ConsumeRequest(
                    new Message('test', 'test data'),
                    $this->createMock(QueueInterface::class),
                );
            }
        );
        self::assertSame(
            'test data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(MessageHandlerConsumeInterface::class)
            )->getMessage()->getData()
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            static function (): MiddlewareConsumeInterface {
                return new TestMiddleware();
            }
        );
        self::assertSame(
            'New middleware test data',
            $middleware->processConsume(
                $this->getConsumeRequest(),
                $this->createMock(MessageHandlerConsumeInterface::class)
            )->getMessage()->getData()
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
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            [TestCallableMiddleware::class, 'index']
        );
        $request = $this->getConsumeRequest();

        self::assertSame(
            'New test data',
            $middleware->processConsume(
                $request,
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            static function () {
                return 42;
            }
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processConsume(
            $this->getConsumeRequest(),
            $this->createMock(MessageHandlerConsumeInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongString(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createConsumeMiddleware('test');
    }

    public function testInvalidMiddlewareWithWrongClass(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createConsumeMiddleware(TestCallableMiddleware::class);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->createConsumeMiddleware(
            [InvalidController::class, 'index']
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processConsume(
            $this->getConsumeRequest(),
            $this->createMock(MessageHandlerConsumeInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongArraySize(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createConsumeMiddleware(['test']);
    }

    public function testInvalidMiddlewareWithWrongArrayClass(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createConsumeMiddleware(['class', 'test']);
    }

    public function testInvalidMiddlewareWithWrongArrayType(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createConsumeMiddleware(['class' => TestCallableMiddleware::class, 'index']);
    }

    public function testInvalidMiddlewareWithWrongArrayWithIntItems(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createConsumeMiddleware([7, 42]);
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryConsumeInterface
    {
        $container = $container ?? $this->getContainer([AdapterInterface::class => new FakeAdapter()]);

        return new MiddlewareFactoryConsume($container, new CallableFactory($container));
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
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

    private function getConsumeRequest(): ConsumeRequest
    {
        return new ConsumeRequest(
            new Message('handler', 'data'),
            $this->createMock(QueueInterface::class)
        );
    }
}
