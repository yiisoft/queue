<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Implementation;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
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
        $arguments[] = static fn(MessageInterface $message): MessageInterface => $message;

        if (!$success) {
            $this->expectException(InvalidArgumentException::class);
        }

        $strategy = new ExponentialDelayMiddleware(...$arguments);
        self::assertInstanceOf(ExponentialDelayMiddleware::class, $strategy);
    }

    public function testPipelineSuccess(): void
    {
        $message = new GenericMessage('test', null);
        $retry = static fn(MessageInterface $message): MessageInterface => $message;
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            $retry,
        );
        $nextHandler = $this->createMock(FailureHandlerInterface::class);
        $nextHandler->expects(self::never())->method('handleFailure');
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue', $retry);
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
        $retry = static fn(MessageInterface $message): MessageInterface => $message;
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            $retry,
        );
        $nextHandler = $this->createMock(FailureHandlerInterface::class);
        $exception = new Exception('test');
        $nextHandler->expects(self::once())->method('handleFailure')->willThrowException($exception);
        $request = new FailureHandlingRequest($message, $exception, 'test-queue', $retry);
        $middleware->processFailure($request, $nextHandler);
    }

    public function testMissingProducerCapability(): void
    {
        $middleware = new ExponentialDelayMiddleware('test', 1, 1, 1, 1);
        $request = new FailureHandlingRequest(
            new GenericMessage('test', null),
            new Exception('test'),
            'test-queue',
        );

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage('configure a producer target or QueueProducerProviderInterface');
        $middleware->processFailure($request, $this->createMock(FailureHandlerInterface::class));
    }

    public function testProducerProviderIsUsedForRetry(): void
    {
        $producer = $this->getProducer();
        $provider = $this->createMock(QueueProducerProviderInterface::class);
        $provider->expects(self::once())->method('getProducer')->with('test-queue')->willReturn($producer);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            producerProvider: $provider,
        );
        $request = new FailureHandlingRequest(
            new GenericMessage('test', null),
            new Exception('test'),
            'test-queue',
        );

        $result = $middleware->processFailure($request, $this->createMock(FailureHandlerInterface::class));

        self::assertNotSame($request->getMessage(), $result->getMessage());
    }

    public function testUnavailableProducerProviderIsReported(): void
    {
        $provider = $this->createMock(QueueProducerProviderInterface::class);
        $provider->expects(self::once())
            ->method('getProducer')
            ->with('test-queue')
            ->willThrowException(new RuntimeException('missing producer'));
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            producerProvider: $provider,
        );
        $request = new FailureHandlingRequest(
            new GenericMessage('test', null),
            new Exception('test'),
            'test-queue',
        );

        try {
            $middleware->processFailure($request, $this->createMock(FailureHandlerInterface::class));
            self::fail('Expected an invalid queue configuration exception.');
        } catch (InvalidQueueConfigException $exception) {
            self::assertStringContainsString('no producer capability is available', $exception->getMessage());
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }
    }

    private function getProducer(): AsyncQueueProducer
    {
        return new AsyncQueueProducer(
            new NullLogger(),
            new PushMiddlewareConfig(new PushMiddlewareFactory(new SimpleContainer())),
            new InMemoryAdapter(),
            'test-queue',
        );
    }
}
