<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Implementation\FailureStrategy\Strategy;

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

        $strategy = new ExponentialDelayStrategy(...$arguments);
        self::assertInstanceOf(ExponentialDelayStrategy::class, $strategy);
    }

    public function testPipelineSuccess(): void
    {
        $message = new Message('test', null);
        $queue = $this->createMock(QueueInterface::class);
        $strategy = new ExponentialDelayStrategy(
            'test',
            1,
            1,
            1,
            1,
            $this->createMock(DelayMiddlewareInterface::class),
            $queue,
        );
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::never())->method('handle');
        $request = new ConsumeRequest($message, $queue);
        $result = $strategy->handle($request, new Exception('test'), $pipeline);

        self::assertNotEquals($request, $result);
        self::assertArrayHasKey(ExponentialDelayStrategy::META_KEY_ATTEMPTS . '-test', $result->getMessage()->getMetadata());
        self::assertArrayHasKey(ExponentialDelayStrategy::META_KEY_DELAY . '-test', $result->getMessage()->getMetadata());
    }

    public function testPipelineFailure(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test');

        $message = new Message('test', null, [ExponentialDelayStrategy::META_KEY_ATTEMPTS . '-test' => 2]);
        $queue = $this->createMock(QueueInterface::class);
        $strategy = new ExponentialDelayStrategy(
            'test',
            1,
            1,
            1,
            1,
            $this->createMock(DelayMiddlewareInterface::class),
            $queue,
        );
        $pipeline = $this->createMock(PipelineInterface::class);
        $exception = new Exception('test');
        $pipeline->expects(self::once())->method('handle')->willThrowException($exception);
        $request = new ConsumeRequest($message, $queue);
        $strategy->handle($request, $exception, $pipeline);
    }
}
