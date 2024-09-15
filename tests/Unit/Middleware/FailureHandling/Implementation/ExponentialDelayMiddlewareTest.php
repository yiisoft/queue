<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Implementation;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\TestCase;

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
        $arguments[] = $this->createMock(DelayMiddlewareInterface::class);
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
        $queue->method('push')->willReturnArgument(0);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            $this->createMock(DelayMiddlewareInterface::class),
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
            [FailureEnvelope::FAILURE_META_KEY => [ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test' => 2]]);
        $queue = $this->createMock(QueueInterface::class);
        $middleware = new ExponentialDelayMiddleware(
            'test',
            1,
            1,
            1,
            1,
            $this->createMock(DelayMiddlewareInterface::class),
            $queue,
        );
        $nextHandler = $this->createMock(MessageFailureHandlerInterface::class);
        $exception = new Exception('test');
        $nextHandler->expects(self::once())->method('handleFailure')->willThrowException($exception);
        $request = new FailureHandlingRequest($message, $exception, $queue);
        $middleware->processFailure($request, $nextHandler);
    }
}
