<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Implementation\FailureStrategy\Strategy;

use Exception;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Throwable;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\DelayMiddlewareInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\ExponentialDelayStrategy;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\FailureStrategyInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\SendAgainStrategy;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

class ResendStrategyTest extends TestCase
{
    private const EXPONENTIAL_STRATEGY_DELAY_INITIAL = 1;
    private const EXPONENTIAL_STRATEGY_DELAY_MAXIMUM = 5;
    private const EXPONENTIAL_STRATEGY_EXPONENT = 2;
    const KEY_EXPONENTIAL_ATTEMPTS = ExponentialDelayStrategy::META_KEY_ATTEMPTS . '-test';
    const KEY_EXPONENTIAL_DELAY = ExponentialDelayStrategy::META_KEY_DELAY . '-test';

    public function queueSendingStrategyProvider(): array
    {
        return [
            [
                SendAgainStrategy::class,
                true,
                [],
                [SendAgainStrategy::META_KEY_RESEND . '-' => 1],
            ],
            [
                SendAgainStrategy::class,
                true,
                [SendAgainStrategy::META_KEY_RESEND . '-' => 1],
                [SendAgainStrategy::META_KEY_RESEND . '-' => 2],
            ],
            [
                SendAgainStrategy::class,
                false,
                [SendAgainStrategy::META_KEY_RESEND . '-' => 2],
                [SendAgainStrategy::META_KEY_RESEND . '-' => 2],
            ],
            [
                SendAgainStrategy::class,
                true,
                [SendAgainStrategy::META_KEY_RESEND . '-' => -1],
                [SendAgainStrategy::META_KEY_RESEND . '-' => 1],
            ],
            [
                SendAgainStrategy::class,
                true,
                [SendAgainStrategy::META_KEY_RESEND . '-' => -100],
                [SendAgainStrategy::META_KEY_RESEND . '-' => 1],
            ],
            [
                SendAgainStrategy::class,
                false,
                [SendAgainStrategy::META_KEY_RESEND . '-' => 5],
                [SendAgainStrategy::META_KEY_RESEND . '-' => 5],
            ],

            [
                ExponentialDelayStrategy::class,
                true,
                [],
                [
                    self::KEY_EXPONENTIAL_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_INITIAL * self::EXPONENTIAL_STRATEGY_EXPONENT,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 1,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    self::KEY_EXPONENTIAL_DELAY => 1,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 1,
                ],
                [
                    self::KEY_EXPONENTIAL_DELAY => 1 * self::EXPONENTIAL_STRATEGY_EXPONENT,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 2,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    self::KEY_EXPONENTIAL_DELAY => 2,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 1,
                ],
                [
                    self::KEY_EXPONENTIAL_DELAY => 2 * self::EXPONENTIAL_STRATEGY_EXPONENT,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 2,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    self::KEY_EXPONENTIAL_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 1,
                ],
                [
                    self::KEY_EXPONENTIAL_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 2,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    self::KEY_EXPONENTIAL_DELAY => 4,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 1,
                ],
                [
                    self::KEY_EXPONENTIAL_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 2,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    self::KEY_EXPONENTIAL_DELAY => 100,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 1,
                ],
                [
                    self::KEY_EXPONENTIAL_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider queueSendingStrategyProvider
     */
    public function testQueueSendingStrategies(
        string $strategyName,
        bool $suites,
        array $metaInitial,
        array $metaResult
    ): void {
        if (!$suites) {
            $this->expectExceptionMessage('testException');
        }

        $pipeline = $this->getPipeline($metaResult, $suites);
        $queue = $this->getPreparedQueue($metaResult, $suites);

        $strategy = $this->getStrategy($strategyName, $queue);
        $request = new ConsumeRequest(new Message('test', null, $metaInitial), $queue);
        $result = $strategy->handle($request, new Exception('testException'), $pipeline);

        self::assertInstanceOf(ConsumeRequest::class, $result);
    }

    private function getStrategy(string $strategyName, QueueInterface $queue): FailureStrategyInterface
    {
        return match ($strategyName) {
            SendAgainStrategy::class => new SendAgainStrategy('', 2, $queue),
            ExponentialDelayStrategy::class => new ExponentialDelayStrategy(
                'test',
                2,
                self::EXPONENTIAL_STRATEGY_DELAY_INITIAL,
                self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                self::EXPONENTIAL_STRATEGY_EXPONENT,
                $this->createMock(DelayMiddlewareInterface::class),
                $queue,
            ),
            default => throw new RuntimeException('Unknown strategy'),
        };
    }

    private function getPipeline(array $metaResult, bool $suites): PipelineInterface
    {
        $pipelineAssertion = static function (ConsumeRequest $request, Throwable $exception) use ($metaResult): ConsumeRequest {
            Assert::assertEquals($metaResult, $request->getMessage()->getMetadata());

            throw $exception;
        };
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects($suites ? self::never() : self::once())
            ->method('handle')
            ->willReturnCallback($pipelineAssertion);

        return $pipeline;
    }

    private function getPreparedQueue(array $metaResult, bool $suites): QueueInterface
    {
        $queueAssertion = static function (MessageInterface $message) use ($metaResult): MessageInterface {
            Assert::assertEquals($metaResult, $message->getMetadata());

            return $message;
        };

        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($suites ? self::once() : self::never())
            ->method('push')
            ->willReturnCallback($queueAssertion);

        return $queue;
    }
}
