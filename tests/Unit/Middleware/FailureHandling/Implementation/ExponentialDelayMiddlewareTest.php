<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Implementation;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\QueueInterface;
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
        $arguments[] = $this->createMock(QueueInterface::class);

        if (!$success) {
            $this->expectException(InvalidArgumentException::class);
        }

        $strategy = new ExponentialDelayMiddleware(...$arguments);
        self::assertInstanceOf(ExponentialDelayMiddleware::class, $strategy);
    }

    public function testPipelineSuccess(): void
    {
        $message = new GenericMessage('test', null);
        $queue = $this->createMock(QueueInterface::class);
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
        $request = new FailureHandlingRequest($message, new Exception('test'), $queue);
        $result = $middleware->processFailure($request, $nextHandler);

        self::assertNotEquals($request, $result);
        $message = $result->getMessage();
        self::assertArrayHasKey(FailureEnvelope::META_FAILURE_METADATA, $message->getMetadata());
        self::assertArrayHasKey(DelayEnvelope::META_DELAY_SECONDS, $message->getMetadata());

        $meta = $message->getMetadata()[FailureEnvelope::META_FAILURE_METADATA];
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
        ))->withMetadata([FailureEnvelope::META_FAILURE_METADATA => [ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test' => 2]]);
        $queue = $this->createMock(QueueInterface::class);
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
        $request = new FailureHandlingRequest($message, $exception, $queue);
        $middleware->processFailure($request, $nextHandler);
    }
}
