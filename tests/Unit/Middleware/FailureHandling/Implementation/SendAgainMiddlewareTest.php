<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Implementation;

use Exception;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\SendAgainMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Tests\TestCase;

final class SendAgainMiddlewareTest extends TestCase
{
    final public const KEY_EXPONENTIAL_ATTEMPTS = ExponentialDelayMiddleware::META_KEY_ATTEMPTS . '-test';
    final public const KEY_EXPONENTIAL_DELAY = ExponentialDelayMiddleware::META_KEY_DELAY . '-test';
    private const EXPONENTIAL_STRATEGY_DELAY_INITIAL = 1;
    private const EXPONENTIAL_STRATEGY_DELAY_MAXIMUM = 5;
    private const EXPONENTIAL_STRATEGY_EXPONENT = 2;

    public static function queueSendingStrategyProvider(): array
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
                    self::KEY_EXPONENTIAL_DELAY => self::EXPONENTIAL_STRATEGY_EXPONENT,
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

    #[DataProvider('queueSendingStrategyProvider')]
    public function testQueueSendingStrategies(
        string $strategyName,
        bool $suites,
        array $metaInitial,
        array $metaResult,
    ): void {
        if (!$suites) {
            $this->expectExceptionMessage('testException');
        }

        $handler = $this->getHandler($metaResult, $suites);
        $queue = $this->getPreparedQueue($metaResult, $suites);

        $strategy = $this->getStrategy($strategyName, $queue);
        $request = new FailureHandlingRequest(
            (new GenericMessage(
                'test',
                null,
            ))->withMeta([FailureEnvelope::META_FAILURE => $metaInitial]),
            new Exception('testException'),
            'test-queue',
        );
        $result = $strategy->processFailure($request, $handler);

        self::assertInstanceOf(FailureHandlingRequest::class, $result);
    }

    public function testProducerIsResolvedFromProviderByQueueName(): void
    {
        $message = new GenericMessage('test', null);
        $producer = $this->createMock(QueueProducerInterface::class);
        $producer->expects(self::once())->method('push')->willReturnArgument(0);
        $provider = $this->createMock(QueueProducerProviderInterface::class);
        $provider->expects(self::once())
            ->method('getProducer')
            ->with('test-queue')
            ->willReturn($producer);
        $handler = $this->createMock(FailureHandlerInterface::class);
        $handler->expects(self::never())->method('handleFailure');
        $middleware = new SendAgainMiddleware('test', 1, null, $provider);
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue');

        $result = $middleware->processFailure($request, $handler);

        self::assertArrayHasKey(FailureEnvelope::META_FAILURE, $result->getMessage()->getMeta());
    }

    public function testFailsWithoutTargetQueueAndProducerProvider(): void
    {
        $message = new GenericMessage('test', null);
        $handler = $this->createMock(FailureHandlerInterface::class);
        $handler->expects(self::never())->method('handleFailure');
        $middleware = new SendAgainMiddleware('test', 1);
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue');

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            'Cannot retry queue "test-queue": configure a producer target or QueueProducerProviderInterface.',
        );
        $middleware->processFailure($request, $handler);
    }

    public function testFailsWhenProviderCannotResolveProducer(): void
    {
        $message = new GenericMessage('test', null);
        $providerException = new RuntimeException('no such queue');
        $provider = $this->createMock(QueueProducerProviderInterface::class);
        $provider->method('getProducer')->willThrowException($providerException);
        $handler = $this->createMock(FailureHandlerInterface::class);
        $handler->expects(self::never())->method('handleFailure');
        $middleware = new SendAgainMiddleware('test', 1, null, $provider);
        $request = new FailureHandlingRequest($message, new Exception('test'), 'test-queue');

        try {
            $middleware->processFailure($request, $handler);
            self::fail('Exception was not thrown.');
        } catch (InvalidQueueConfigException $exception) {
            self::assertSame(
                'Cannot retry queue "test-queue": no producer capability is available.',
                $exception->getMessage(),
            );
            self::assertSame($providerException, $exception->getPrevious());
        }
    }

    private function getStrategy(string $strategyName, QueueProducerInterface $queue): FailureMiddlewareInterface
    {
        return match ($strategyName) {
            SendAgainMiddleware::class => new SendAgainMiddleware('', 2, $queue),
            ExponentialDelayMiddleware::class => new ExponentialDelayMiddleware(
                'test',
                2,
                self::EXPONENTIAL_STRATEGY_DELAY_INITIAL,
                self::EXPONENTIAL_STRATEGY_DELAY_MAXIMUM,
                self::EXPONENTIAL_STRATEGY_EXPONENT,
                $queue,
            ),
            default => throw new RuntimeException('Unknown strategy'),
        };
    }

    private function getHandler(array $metaResult, bool $suites): FailureHandlerInterface
    {
        $pipelineAssertion = static function (FailureHandlingRequest $request) use (
            $metaResult
        ): FailureHandlingRequest {
            Assert::assertEquals($metaResult, $request->getMessage()->getMeta()[FailureEnvelope::META_FAILURE] ?? []);

            throw $request->getException();
        };
        $handler = $this->createMock(FailureHandlerInterface::class);
        $handler->expects($suites ? self::never() : self::once())
            ->method('handleFailure')
            ->willReturnCallback($pipelineAssertion);

        return $handler;
    }

    private function getPreparedQueue(array $metaResult, bool $suites): QueueProducerInterface
    {
        $queueAssertion = static function (MessageInterface $message) use ($metaResult): MessageInterface {
            Assert::assertEquals($metaResult, $message->getMeta()[FailureEnvelope::META_FAILURE] ?? []);

            return $message;
        };

        $queue = $this->createMock(QueueProducerInterface::class);
        $queue->expects($suites ? self::once() : self::never())
            ->method('push')
            ->willReturnCallback($queueAssertion);

        return $queue;
    }
}
