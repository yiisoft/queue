<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Implementation;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\StubDelayMiddleware;
use Yiisoft\Queue\Tests\TestCase;

use const PHP_INT_MAX;

class ExponentialDelayMiddlewareTest extends TestCase
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
        $arguments[] = new StubDelayMiddleware();
        $arguments[] = $this->createMock(QueueInterface::class);

        if (!$success) {
            $this->expectException(InvalidArgumentException::class);
        }

        $strategy = new ExponentialDelayMiddleware(...$arguments);
        self::assertInstanceOf(ExponentialDelayMiddleware::class, $strategy);
    }

    public function testPipelineSuccess(): void
    {
        $message = new Message('test', null);
        $queue = $this->createMock(QueueInterface::class);
        $queue->method('withMiddlewaresAdded')->willReturnSelf();
        $queue->method('push')->willReturnArgument(0);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            new StubDelayMiddleware(),
            $queue,
        );
        $nextHandler = $this->createMock(MessageFailureHandlerInterface::class);
        $nextHandler->expects(self::never())->method('handleFailure');
        $request = new FailureHandlingRequest($message, new Exception('test'), $queue);
        $result = $middleware->processFailure($request, $nextHandler);

        self::assertNotEquals($request, $result);
        $message = $result->getMessage();
        self::assertArrayHasKey(FailureEnvelope::FAILURE_META_KEY, $message->getMetadata());

        $meta = $message->getMetadata()[FailureEnvelope::FAILURE_META_KEY];
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test', $meta);
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_DELAY . '-test', $meta);
    }

    public function testPipelineFailure(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test');

        $message = new Message(
            'test',
            null,
            [FailureEnvelope::FAILURE_META_KEY => [ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test' => 2]],
        );
        $queue = $this->createMock(QueueInterface::class);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            new StubDelayMiddleware(),
            $queue,
        );
        $nextHandler = $this->createMock(MessageFailureHandlerInterface::class);
        $exception = new Exception('test');
        $nextHandler->expects(self::once())->method('handleFailure')->willThrowException($exception);
        $request = new FailureHandlingRequest($message, $exception, $queue);
        $middleware->processFailure($request, $nextHandler);
    }

    public function testDelayMiddlewareWrapsActualRetryPush(): void
    {
        $message = new Message('test', null);
        $adapter = new DelayAwareAdapter();
        $queue = $this->createQueue($adapter);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            new AdapterContextDelayMiddleware($adapter),
            $queue,
        );

        $request = new FailureHandlingRequest($message, new Exception('test'), $queue);
        $middleware->processFailure($request, new ThrowingFailureHandler());

        self::assertSame([1.0], $adapter->delaysDuringPush);
    }
}

final class AdapterContextDelayMiddleware implements DelayMiddlewareInterface
{
    private float $delay = 0.0;

    public function __construct(
        private readonly DelayAwareAdapter $adapter,
    ) {}

    public function withDelay(float $seconds): self
    {
        $new = clone $this;
        $new->delay = $seconds;

        return $new;
    }

    public function processPush(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface
    {
        $this->adapter->activeDelay = $this->delay;

        try {
            return $handler->handlePush($message);
        } finally {
            $this->adapter->activeDelay = null;
        }
    }
}

final class DelayAwareAdapter implements AdapterInterface
{
    /**
     * @var list<float|null>
     */
    public array $delaysDuringPush = [];

    public ?float $activeDelay = null;

    public function runExisting(callable $handlerCallback): void {}

    public function status(string|int $id): MessageStatus
    {
        return MessageStatus::DONE;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $this->delaysDuringPush[] = $this->activeDelay;

        return $message;
    }

    public function subscribe(callable $handlerCallback): void {}
}

final class ThrowingFailureHandler implements MessageFailureHandlerInterface
{
    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
    {
        throw $request->getException();
    }
}
