<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;
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
        $this->getMiddlewareFactory()->createFailureMiddleware($definition);
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

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryFailureInterface
    {
        $container ??= $this->getContainer([AdapterInterface::class => new FakeAdapter()]);

        return new MiddlewareFactoryFailure($container, new CallableFactory($container));
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
