<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Yiisoft\Yii\Queue\FailureStrategy\ExponentialDelayStrategy;
use Yiisoft\Yii\Queue\FailureStrategy\FailureStrategyInterface;
use Yiisoft\Yii\Queue\FailureStrategy\PipelineInterface;
use Yiisoft\Yii\Queue\FailureStrategy\SendAgainStrategy;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\TestCase;

class ResendStrategyTest extends TestCase
{
    private const EXPONENTIAL_STRATEGY_DELAY_INITIAL = 1;
    private const EXPONENTIAL_STRATEGY_DELAY_MAXIMUM = 5;
    private const EXPONENTIAL_STRATEGY_EXPONENT = 2;

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
                    ExponentialDelayStrategy::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_INITIAL * self::EXPONENTIAL_STRATEGY_EXPONENT,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                    PayloadInterface::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_INITIAL * self::EXPONENTIAL_STRATEGY_EXPONENT,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => 1,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                ],
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => 1 * self::EXPONENTIAL_STRATEGY_EXPONENT,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2,
                    PayloadInterface::META_KEY_DELAY => 1 * self::EXPONENTIAL_STRATEGY_EXPONENT,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => 2,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                ],
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => 2 * self::EXPONENTIAL_STRATEGY_EXPONENT,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2,
                    PayloadInterface::META_KEY_DELAY => 2 * self::EXPONENTIAL_STRATEGY_EXPONENT,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                ],
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2,
                    PayloadInterface::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => 4,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                ],
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2,
                    PayloadInterface::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                ],
            ],
            [
                ExponentialDelayStrategy::class,
                true,
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => 100,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                ],
                [
                    ExponentialDelayStrategy::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2,
                    PayloadInterface::META_KEY_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                ],
            ],
        ];
    }


    /**
     * @dataProvider queueSendingStrategyProvider
     *
     * @param string $strategyName
     * @param bool $suites
     * @param array $metaInitial
     * @param array $metaResult
     */
    public function testQueueSendingStrategies(
        string $strategyName,
        bool $suites,
        array $metaInitial,
        array $metaResult
    ): void {
        $pipeline = $this->getPipeline($metaResult, $suites);
        $queue = $this->getPreparedQueue($metaResult, $suites);

        $strategy = $this->getStrategy($strategyName, $queue);

        $message = new Message('test', null, $metaInitial);
        $result = $strategy->handle($message, $pipeline);

        self::assertEquals($suites, $result);
    }

    private function getStrategy(string $strategyName, Queue $queue): FailureStrategyInterface
    {
        switch ($strategyName) {
            case SendAgainStrategy::class:
                return new SendAgainStrategy('', 2, $queue, new PayloadFactory());
            case ExponentialDelayStrategy::class:
                return new ExponentialDelayStrategy(
                    2,
                    self::EXPONENTIAL_STRATEGY_DELAY_INITIAL,
                    self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                    self::EXPONENTIAL_STRATEGY_EXPONENT,
                    new PayloadFactory(),
                    $queue
                );
            default:
                throw new RuntimeException('Unknown strategy');
        }
    }

    /**
     * @param array $metaResult
     * @param bool $suites
     *
     * @return MockObject|PipelineInterface
     */
    private function getPipeline(array $metaResult, bool $suites): PipelineInterface
    {
        $pipelineAssertion = static function (MessageInterface $message) use ($metaResult) {
            Assert::assertEquals($metaResult, $message->getPayloadMeta());

            return false;
        };
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects($suites ? self::never() : self::once())
            ->method('handle')
            ->willReturnCallback($pipelineAssertion);

        return $pipeline;
    }

    /**
     * @param array $metaResult
     * @param bool $suites
     *
     * @return MockObject|Queue
     */
    private function getPreparedQueue(array $metaResult, bool $suites): Queue
    {
        $queueAssertion = static function (PayloadInterface $payload) use ($metaResult) {
            Assert::assertEquals($metaResult, $payload->getMeta());

            return null;
        };

        $queue = $this->createMock(Queue::class);
        $queue->expects($suites ? self::once() : self::never())
            ->method('push')
            ->willReturnCallback($queueAssertion);

        return $queue;
    }

    public function delayZeroProvider(): array
    {
        return [
            'empty meta' => [
                [],
                [
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                    ExponentialDelayStrategy::META_KEY_DELAY => 0,
                    PayloadInterface::META_KEY_DELAY => 0,
                ],
            ],
            'zero delay in meta' => [
                [
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                    ExponentialDelayStrategy::META_KEY_DELAY => 0,
                ],
                [
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2,
                    ExponentialDelayStrategy::META_KEY_DELAY => 1,
                    PayloadInterface::META_KEY_DELAY => 1,
                ],
            ],
            'positive delay in meta' => [
                [
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 1,
                    ExponentialDelayStrategy::META_KEY_DELAY => 2,
                ],
                [
                    ExponentialDelayStrategy::META_KEY_ATTEMPTS => 2,
                    ExponentialDelayStrategy::META_KEY_DELAY => 4,
                    PayloadInterface::META_KEY_DELAY => 4,
                ],
            ],
        ];
    }

    /**
     * @dataProvider delayZeroProvider
     *
     * @param array $messageMeta
     * @param array $resultMeta
     */
    public function testDelayZero(array $messageMeta, array $resultMeta): void
    {
        $payloadFactory = new PayloadFactory();
        $queueAssertion = static function (PayloadInterface $payload) use ($resultMeta) {
            Assert::assertEquals($resultMeta, $payload->getMeta());

            return null;
        };

        $queue = $this->createMock(Queue::class);
        $queue->expects(self::once())
            ->method('push')
            ->willReturnCallback($queueAssertion);

        $strategy = new ExponentialDelayStrategy(
            5,
            0,
            5,
            2,
            $payloadFactory,
            $queue
        );
        $pipeline = $this->createMock(PipelineInterface::class);
        $message = new Message('simple', null, $messageMeta);
        self::assertTrue($strategy->handle($message, $pipeline));
    }
}
