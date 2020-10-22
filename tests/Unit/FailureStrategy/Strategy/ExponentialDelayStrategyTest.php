<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\FailureStrategy\Strategy;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\FailureStrategy\Strategy\ExponentialDelayStrategy;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\TestCase;

class ExponentialDelayStrategyTest extends TestCase
{
    public function constructorRequirementsProvider(): array
    {
        $queue = $this->createMock(Queue::class);
        $factory = $this->createMock(PayloadFactory::class);

        return [
            [
                true,
                [
                    1,
                    0,
                    1,
                    0.01,
                    $factory,
                    $queue,
                ],
            ],
            [
                true,
                [
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    $factory,
                    $queue,
                ],
            ],
            [
                false,
                [
                    0,
                    0,
                    1,
                    0.01,
                    $factory,
                    $queue,
                ],
            ],
            [
                false,
                [
                    1,
                    0,
                    0,
                    0.01,
                    $factory,
                    $queue,
                ],
            ],
            [
                false,
                [
                    1,
                    0,
                    0.01,
                    0,
                    $factory,
                    $queue,
                ],
            ],
            [
                false,
                [
                    0,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    $factory,
                    $queue,
                ],
            ],
            [
                false,
                [
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    0,
                    PHP_INT_MAX,
                    $factory,
                    $queue,
                ],
            ],
            [
                false,
                [
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    PHP_INT_MAX,
                    0,
                    $factory,
                    $queue,
                ],
            ],
        ];
    }

    /**
     * @dataProvider constructorRequirementsProvider
     *
     * @param bool $success
     * @param array $arguments
     */
    public function testConstructorRequirements(bool $success, array $arguments): void
    {
        if (!$success) {
            $this->expectException(InvalidArgumentException::class);
        }

        $strategy = new ExponentialDelayStrategy(...$arguments);
        self::assertInstanceOf(ExponentialDelayStrategy::class, $strategy);
    }

    public function testReturnTrueFromPipeline(): void
    {
        $message = new Message('test', null, [ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2]);
        $strategy = new ExponentialDelayStrategy(1, 1, 1, 1, $this->createMock(PayloadFactory::class), $this->createMock(Queue::class));
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willReturn(true);
        $result = $strategy->handle($message, $pipeline);

        self::assertTrue($result);
    }

    public function testReturnFalseFromPipeline(): void
    {
        $message = new Message('test', null, [ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2]);
        $strategy = new ExponentialDelayStrategy(1, 1, 1, 1, $this->createMock(PayloadFactory::class), $this->createMock(Queue::class));
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willReturn(false);
        $result = $strategy->handle($message, $pipeline);

        self::assertFalse($result);
    }

    public function testReturnFalseWithoutPipeline(): void
    {
        $message = new Message('test', null, [ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2]);
        $strategy = new ExponentialDelayStrategy(1, 1, 1, 1, $this->createMock(PayloadFactory::class), $this->createMock(Queue::class));
        $result = $strategy->handle($message, null);

        self::assertFalse($result);
    }
}
