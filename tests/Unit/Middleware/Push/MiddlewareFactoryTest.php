<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
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
                $this->getPushRequest(),
                $this->createMock(MessageHandlerPushInterface::class)
            )->getMessage()->getData(),
        );
    }

    public function testCreateFromClosureResponse(): void
    {
        $container = $this->getContainer([TestCallableMiddleware::class => new TestCallableMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            static function (): PushRequest {
                return new PushRequest(new Message('test', 'test data'), new FakeAdapter());
            }
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
            static function (): MiddlewarePushInterface {
                return new TestMiddleware();
            }
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
        $this->getMiddlewareFactory()->createPushMiddleware($definition);
    }

    public function testInvalidMiddlewareWithWrongController(): void
    {
        $container = $this->getContainer([InvalidController::class => new InvalidController()]);
        $middleware = $this->getMiddlewareFactory($container)->createPushMiddleware(
            [InvalidController::class, 'index']
        );

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processPush(
            $this->getPushRequest(),
            $this->createMock(MessageHandlerPushInterface::class)
        );
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
