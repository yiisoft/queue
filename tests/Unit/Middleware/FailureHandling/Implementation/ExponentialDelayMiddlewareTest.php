<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Implementation;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Message\DelayEnvelope;
use Yiisoft\Queue\Tests\TestCase;

use const PHP_INT_MAX;

final class ExponentialDelayMiddlewareTest extends TestCase
{
    public static function constructorRequirementsProvider(): array
    {
        return [
            [
                true,
                [
                    'test',
                    1,
                    0.001,
                    1,
                    0.01,
                ],
            ],
            [
                true,
                [
                    'test',
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                ],
            ],
            [
                false,
                [
                    'test',
                    1,
                    0,
                    1,
                    0.01,
                ],
            ],
            [
                false,
                [
                    'test',
                    0,
                    0,
                    1,
                    0.01,
                ],
            ],
            [
                false,
                [
                    'test',
                    1,
                    0,
                    0,
                    0.01,
                ],
            ],
            [
                false,
                [
                    'test',
                    1,
                    0,
                    0.01,
                    0,
                ],
            ],
            [
                false,
                [
                    'test',
                    0,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                ],
            ],
            [
                false,
                [
                    'test',
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    0,
                    PHP_INT_MAX,
                ],
            ],
            [
                false,
                [
                    'test',
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    0,
                ],
            ],
        ];
    }

    #[DataProvider('constructorRequirementsProvider')]
    public function testConstructorRequirements(bool $success, array $arguments): void
    {
        $arguments[] = $this->createMock(QueueProducerInterface::class);

        if (!$success) {
            $this->expectException(InvalidArgumentException::class);
        }

        $strategy = new ExponentialDelayMiddleware(...$arguments);
        self::assertInstanceOf(ExponentialDelayMiddleware::class, $strategy);
    }

    public function testPipelineSuccess(): void
    {
        $message = new GenericMessage('test', null);
        $queue = $this->createMock(QueueProducerInterface::class);
        $queue->method('push')->willReturnArgument(0);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            $queue,
        );
        $nextHandler = $this->createMock(FailureHandlerInterface::class);
        $nextHandler->expects(self::never())->method('handleFailure');
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue');
        $result = $middleware->processFailure($request, $nextHandler);

        self::assertNotEquals($request, $result);
        $message = $result->getMessage();
        self::assertArrayHasKey(FailureEnvelope::META_FAILURE, $message->getMeta());
        self::assertArrayHasKey(DelayEnvelope::META_DELAY_SECONDS, $message->getMeta());

        $meta = $message->getMeta()[FailureEnvelope::META_FAILURE];
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test', $meta);
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_DELAY . '-test', $meta);
    }

    public function testPipelineFailure(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test');

        $message = (new GenericMessage(
            'test',
            null,
        ))->withMeta([FailureEnvelope::META_FAILURE => [ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test' => 2]]);
        $queue = $this->createMock(QueueProducerInterface::class);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            $queue,
        );
        $nextHandler = $this->createMock(FailureHandlerInterface::class);
        $exception = new Exception('test');
        $nextHandler->expects(self::once())->method('handleFailure')->willThrowException($exception);
        $request = new FailureHandlingRequest($message, $exception, 'test-queue');
        $middleware->processFailure($request, $nextHandler);
    }

    public function testProducerIsResolvedFromProviderByQueueName(): void
    {
        $message = new GenericMessage('test', null);
        $producer = $this->createMock(QueueProducerInterface::class);
        $producer->expects(self::once())->method('push')->willReturnArgument(0);
        $provider = $this->createMock(QueueProducerProviderInterface::class);
        $provider->expects(self::once())
            ->method('getProducer')
            ->with('test-queue')
            ->willReturn($producer);
        $handler = $this->createMock(FailureHandlerInterface::class);
        $handler->expects(self::never())->method('handleFailure');
        $middleware = new ExponentialDelayMiddleware('test', 1, 1, 1, 1, null, $provider);
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue');

        $result = $middleware->processFailure($request, $handler);

        self::assertArrayHasKey(DelayEnvelope::META_DELAY_SECONDS, $result->getMessage()->getMeta());
    }

    public function testFailsWithoutQueueAndProducerProvider(): void
    {
        $message = new GenericMessage('test', null);
        $handler = $this->createMock(FailureHandlerInterface::class);
        $handler->expects(self::never())->method('handleFailure');
        $middleware = new ExponentialDelayMiddleware('test', 1, 1, 1, 1);
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue');

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            'Cannot retry queue "test-queue": configure a producer target or QueueProducerProviderInterface.',
        );
        $middleware->processFailure($request, $handler);
    }

    public function testFailsWhenProviderCannotResolveProducer(): void
    {
        $message = new GenericMessage('test', null);
        $providerException = new RuntimeException('no such queue');
        $provider = $this->createMock(QueueProducerProviderInterface::class);
        $provider->method('getProducer')->willThrowException($providerException);
        $handler = $this->createMock(FailureHandlerInterface::class);
        $handler->expects(self::never())->method('handleFailure');
        $middleware = new ExponentialDelayMiddleware('test', 1, 1, 1, 1, null, $provider);
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue');

        try {
            $middleware->processFailure($request, $handler);
            self::fail('Exception was not thrown.');
        } catch (InvalidQueueConfigException $exception) {
            self::assertSame(
                'Cannot retry queue "test-queue": no producer capability is available.',
                $exception->getMessage(),
            );
            self::assertSame($providerException, $exception->getPrevious());
        }
    }
}
