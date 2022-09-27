<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Yii\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support\InvalidController;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support\TestCallableMiddleware;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support\TestMiddleware;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(TestMiddleware::class);
        self::assertInstanceOf(TestMiddleware::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware([TestCallableMiddleware::class, 'index']);
        self::assertSame(
            'New test data',
            $middleware->processPush(
                $this->getPushRequest(),
                $this->createMock(MessageHandlerPushInterface::class)
            )->getMessage()->getData(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            static fn(): PushRequest => new PushRequest(new Message('test', 'test data'), new FakeAdapter())
        );
        self::assertSame(
            'test data',
            $middleware->processPush(
                $this->getPushRequest(),
                $this->createMock(MessageHandlerPushInterface::class)
            )->getMessage()->getData()
        );
    }

    public function testCreateFromClosureMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            static fn(): MiddlewarePushInterface => new TestMiddleware()
        );
        self::assertSame(
            'New middleware test data',
            $middleware->processPush(
                $this->getPushRequest(),
                $this->createMock(MessageHandlerPushInterface::class)
            )->getMessage()->getData()
        );
    }

    public function testCreateWithUseParamsMiddleware(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(TestMiddleware::class);

        self::assertSame(
            'New middleware test data',
            $middleware->processPush(
                $this->getPushRequest(),
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testCreateWithTestCallableMiddleware(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware([TestCallableMiddleware::class, 'index']);
        $request = $this->getPushRequest();

        self::assertSame(
            'New test data',
            $middleware->processPush(
                $request,
                $this->getRequestHandler()
            )->getMessage()->getData()
        );
    }

    public function testInvalidMiddlewareWithWrongCallable(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            static fn() => 42
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processPush(
            $this->getPushRequest(),
            $this->createMock(MessageHandlerPushInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongString(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createPushMiddleware('test');
    }

    public function testInvalidMiddlewareWithWrongClass(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createPushMiddleware(TestCallableMiddleware::class);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware([InvalidController::class, 'index']);

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processPush(
            $this->getPushRequest(),
            $this->createMock(MessageHandlerPushInterface::class)
        );
    }

    public function testInvalidMiddlewareWithWrongArraySize(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createPushMiddleware(['test']);
    }

    public function testInvalidMiddlewareWithWrongArrayClass(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createPushMiddleware(['class', 'test']);
    }

    public function testInvalidMiddlewareWithWrongArrayType(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createPushMiddleware(['class' => TestCallableMiddleware::class, 'index']);
    }

    public function testInvalidMiddlewareWithWrongArrayWithIntItems(): void
    {
        $this->expectException(InvalidCallableConfigurationException::class);
        $this->getMiddlewareFactory()->createPushMiddleware([7, 42]);
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryPushInterface
    {
        $container ??= $this->getContainer([AdapterInterface::class => new FakeAdapter()]);

        return new MiddlewareFactoryPush($container, new CallableFactory($container));
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }

    private function getRequestHandler(): MessageHandlerPushInterface
    {
        return new class () implements MessageHandlerPushInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                return $request;
            }
        };
    }

    private function getPushRequest(): PushRequest
    {
        return new PushRequest(new Message('handler', 'data'), new FakeAdapter());
    }
}
