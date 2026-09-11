<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\SendAgainMiddleware;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;

/**
 * Failure handling behavior of {@see SyncQueueProducer}.
 */
final class SynchronousFailureHandlingTest extends TestCase
{
    public function testFailurePipelineIsExecuted(): void
    {
        $middleware = new class implements FailureMiddlewareInterface {
            /**
             * @var string[]
             */
            public array $seen = [];

            public function processFailure(
                FailureHandlingRequest $request,
                FailureHandlerInterface $handler,
            ): FailureHandlingRequest {
                $this->seen[] = $request->getQueueName();

                return $handler->handleFailure($request);
            }
        };
        $producer = $this->createSyncProducer([FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [$middleware]]);

        try {
            $producer->push(new GenericMessage('exceptional', null));
            self::fail('Exception was not thrown.');
        } catch (MessageFailureException $exception) {
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }

        self::assertSame(['sync-queue'], $middleware->seen);
    }

    public function testResendWithoutExplicitTargetFails(): void
    {
        $producer = $this->createSyncProducer([
            FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [new SendAgainMiddleware('test', 1)],
        ]);

        try {
            $producer->push(new GenericMessage('exceptional', null));
            self::fail('Exception was not thrown.');
        } catch (MessageFailureException $exception) {
            $previous = $exception->getPrevious();
            self::assertInstanceOf(InvalidQueueConfigException::class, $previous);
            self::assertSame(
                'Cannot retry queue "sync-queue": configure a producer target or QueueProducerProviderInterface.',
                $previous->getMessage(),
            );
        }

        self::assertSame(1, $this->executionTimes);
    }

    public function testResendToExplicitAsyncTargetSucceeds(): void
    {
        $adapter = new InMemoryAdapter();
        $deadLetter = new AsyncQueueProducer(
            new NullLogger(),
            $this->getPushMiddlewareConfig(),
            $adapter,
            'dead-letter',
        );
        $producer = $this->createSyncProducer([
            FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [new SendAgainMiddleware('test', 1, $deadLetter)],
        ]);

        $producer->push(new GenericMessage('exceptional', null));

        self::assertCount(1, $adapter->getMessagesList());
        self::assertSame(1, $this->executionTimes);
    }

    private function createSyncProducer(array $failureMiddlewareDefinitions): SyncQueueProducer
    {
        $container = new SimpleContainer();
        $worker = new Worker(
            new NullLogger(),
            new ConsumeMiddlewareDispatcher(new ConsumeMiddlewareFactory($container)),
            new FailureMiddlewareDispatcher(
                new FailureMiddlewareFactory($container),
                $failureMiddlewareDefinitions,
            ),
            new HandlerResolver($this->getMessageHandlers(), $container),
        );

        return new SyncQueueProducer(
            new NullLogger(),
            new PushMiddlewareConfig(new PushMiddlewareFactory($container)),
            $worker,
            'sync-queue',
        );
    }
}
