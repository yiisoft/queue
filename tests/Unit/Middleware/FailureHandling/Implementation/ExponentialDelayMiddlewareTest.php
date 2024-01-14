<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use Exception;
use InvalidArgumentException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\DelayMiddlewareInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\TestCase;

class ExponentialDelayMiddlewareTest extends TestCase
{
    public function constructorRequirementsProvider(): array
    {
        $queue = $this->createMock(QueueInterface::class);
        $middleware = $this->createMock(DelayMiddlewareInterface::class);

        return [
            [
                true,
                [
                    'test',
                    1,
                    0.001,
                    1,
                    0.01,
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
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
                    $middleware,
                    $queue,
                ],
            ],
        ];
    }

    /**
     * @dataProvider constructorRequirementsProvider
     */
    public function testConstructorRequirements(bool $success, array $arguments): void
    {
        if (!$success) {
            $this->expectException(InvalidArgumentException::class);
        }

        $strategy = new ExponentialDelayMiddleware(...$arguments);
        self::assertInstanceOf(ExponentialDelayMiddleware::class, $strategy);
    }

    public function testPipelineSuccess(): void
    {
        $message = new Message(null);
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
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test', $message->getMetadata());
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_DELAY . '-test', $message->getMetadata());
    }

    public function testPipelineFailure(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test');

        $message = new Message(null, [ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test' => 2]);
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
