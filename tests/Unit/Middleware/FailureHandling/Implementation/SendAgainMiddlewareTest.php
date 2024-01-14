<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Implementation;

use Exception;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\SendAgainMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;
use Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\TestCase;

class SendAgainMiddlewareTest extends TestCase
{
    private const EXPONENTIAL_STRATEGY_DELAY_INITIAL = 1;
    private const EXPONENTIAL_STRATEGY_DELAY_MAXIMUM = 5;
    private const EXPONENTIAL_STRATEGY_EXPONENT = 2;
    public const KEY_EXPONENTIAL_ATTEMPTS = ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test';
    public const KEY_EXPONENTIAL_DELAY = ExponentialDelayMiddleware::META_KEY_DELAY . '-test';

    public function queueSendingStrategyProvider(): array
    {
        return [
            /*[
                SendAgainMiddleware::class,
                true,
                [],
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 1],
            ],
            [
                SendAgainMiddleware::class,
                true,
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 1],
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 2],
            ],*/
            [
                SendAgainMiddleware::class,
                false,
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 2],
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 2],
            ],
            [
                SendAgainMiddleware::class,
                true,
                [SendAgainMiddleware::META_KEY_RESEND . '-' => -1],
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 1],
            ],
            [
                SendAgainMiddleware::class,
                true,
                [SendAgainMiddleware::META_KEY_RESEND . '-' => -100],
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 1],
            ],
            [
                SendAgainMiddleware::class,
                false,
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 5],
                [SendAgainMiddleware::META_KEY_RESEND . '-' => 5],
            ],

            [
                ExponentialDelayMiddleware::class,
                true,
                [],
                [
                    self::KEY_EXPONENTIAL_DELAY => self::EXPONENTIAL_STRATEGY_DELAY_INITIAL * self::EXPONENTIAL_STRATEGY_EXPONENT,
                    self::KEY_EXPONENTIAL_ATTEMPTS => 1,
                ],
            ],
            [
                ExponentialDelayMiddleware::class,
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
                ExponentialDelayMiddleware::class,
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
                ExponentialDelayMiddleware::class,
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
                ExponentialDelayMiddleware::class,
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
                ExponentialDelayMiddleware::class,
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

        $handler = $this->getHandler($metaResult, $suites);
        $queue = $this->getPreparedQueue($metaResult, $suites);

        $strategy = $this->getStrategy($strategyName, $queue);
        $request = new FailureHandlingRequest(
            new Message(
                null,
                $metaInitial
            ),
            new Exception('testException'),
            $queue
        );
        $result = $strategy->processFailure($request, $handler);

        self::assertInstanceOf(FailureHandlingRequest::class, $result);
    }

    private function getStrategy(string $strategyName, QueueInterface $queue): MiddlewareFailureInterface
    {
        return match ($strategyName) {
            SendAgainMiddleware::class => new SendAgainMiddleware('', 2, $queue),
            ExponentialDelayMiddleware::class => new ExponentialDelayMiddleware(
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

    private function getHandler(array $metaResult, bool $suites): MessageFailureHandlerInterface
    {
        $pipelineAssertion = static function (FailureHandlingRequest $request) use (
            $metaResult
        ): FailureHandlingRequest {
            Assert::assertEquals($metaResult, $request->getMessage()->getMetadata());

            throw $request->getException();
        };
        $handler = $this->createMock(MessageFailureHandlerInterface::class);
        $handler->expects($suites ? self::never() : self::once())
            ->method('handleFailure')
            ->willReturnCallback($pipelineAssertion);

        return $handler;
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
