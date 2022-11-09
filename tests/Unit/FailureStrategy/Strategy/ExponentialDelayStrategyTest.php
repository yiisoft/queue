<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\FailureStrategy\Strategy;

use Exception;
use InvalidArgumentException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\DelayMiddlewareInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\ExponentialDelayStrategy;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

class ExponentialDelayStrategyTest extends TestCase
{
    public function constructorRequirementsProvider(): array
    {
        $queue = $this->createMock(QueueInterface::class);
        $middleware = $this->createMock(DelayMiddlewareInterface::class);

        return [
            [
                true,
                [
                    1,
                    0,
                    1,
                    0.01,
                    $queue,
                    $middleware,
                ],
            ],
            [
                true,
                [
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    $queue,
                    $middleware,
                ],
            ],
            [
                false,
                [
                    0,
                    0,
                    1,
                    0.01,
                    $queue,
                    $middleware,
                ],
            ],
            [
                false,
                [
                    1,
                    0,
                    0,
                    0.01,
                    $queue,
                    $middleware,
                ],
            ],
            [
                false,
                [
                    1,
                    0,
                    0.01,
                    0,
                    $queue,
                    $middleware,
                ],
            ],
            [
                false,
                [
                    0,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    $queue,
                    $middleware,
                ],
            ],
            [
                false,
                [
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    0,
                    PHP_INT_MAX,
                    $queue,
                    $middleware,
                ],
            ],
            [
                false,
                [
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    0,
                    $queue,
                    $middleware,
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

        $strategy = new ExponentialDelayStrategy(...$arguments);
        self::assertInstanceOf(ExponentialDelayStrategy::class, $strategy);
    }

    public function testPipelineSuccess(): void
    {
        $message = new Message('test', null);
        $queue = $this->createMock(QueueInterface::class);
        $strategy = new ExponentialDelayStrategy(
            1,
            1,
            1,
            1,
            $queue,
            $this->createMock(DelayMiddlewareInterface::class)
        );
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::never())->method('handle');
        $request = new ConsumeRequest($message, $queue);
        $result = $strategy->handle($request, new Exception('test'), $pipeline);

        self::assertNotEquals($request, $result);
        self::assertArrayHasKey(ExponentialDelayStrategy::META_KEY_ATTEMPTS, $result->getMessage()->getMetadata());
        self::assertArrayHasKey(ExponentialDelayStrategy::META_KEY_DELAY, $result->getMessage()->getMetadata());
    }

    public function testPipelineFailure(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test');

        $message = new Message('test', null, [ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2]);
        $queue = $this->createMock(QueueInterface::class);
        $strategy = new ExponentialDelayStrategy(
            1,
            1,
            1,
            1,
            $queue,
            $this->createMock(DelayMiddlewareInterface::class)
        );
        $pipeline = $this->createMock(PipelineInterface::class);
        $exception = new Exception('test');
        $pipeline->expects(self::once())->method('handle')->willThrowException($exception);
        $request = new ConsumeRequest($message, $queue);
        $strategy->handle($request, $exception, $pipeline);
    }
}
