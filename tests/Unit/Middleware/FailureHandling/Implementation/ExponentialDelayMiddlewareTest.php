<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\DelayMiddlewareInterface;
use Yiisoft\Queue\Middleware\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\Request;
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
        $nextHandler = $this->createMock(MessageHandlerInterface::class);
        $nextHandler->expects(self::never())->method('handle');
        $request = new Request($message, null);
        $result = $middleware->process($request, $nextHandler);

        self::assertNotSame($request, $result);
        $message = $result->getMessage();
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test', $message->getMetadata());
        self::assertArrayHasKey(ExponentialDelayMiddleware::META_KEY_DELAY . '-test', $message->getMetadata());
    }

    public function testPipelineFailure(): void
    {
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
        $nextHandler = $this->createMock(MessageHandlerInterface::class);
        $exception = new Exception('test');
        $nextHandler->expects(self::once())->method('handle')->willThrowException($exception);
        $request = new Request($message, null);

        $this->expectExceptionObject($exception);
        $middleware->process($request, $nextHandler);
    }
}
