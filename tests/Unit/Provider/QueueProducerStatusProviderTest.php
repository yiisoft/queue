<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Provider\QueueProducerStatusProvider;
use Yiisoft\Queue\QueueProducerStatusInterface;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Worker\Worker;

final class QueueProducerStatusProviderTest extends TestCase
{
    public function testLooksUpStatusesByNormalizedQueueNameAndIsLazy(): void
    {
        $status = $this->createMock(QueueProducerStatusInterface::class);
        $status->expects(self::once())->method('status')->with('id')->willReturn(MessageStatus::DONE);
        $adapter = $this->createMock(AdapterInterface::class);
        $producer = new AsyncQueueProducer(new NullLogger(), $this->createPushMiddlewareConfig(), $adapter, status: $status);
        $provider = new QueueProducerStatusProvider(new PredefinedQueueProvider(['red' => ['producer' => $producer]]));

        self::assertTrue($provider->hasStatus(StringEnum::RED));
        self::assertSame(['red'], $provider->getStatusQueueNames());
        self::assertSame($status, $provider->getStatus(StringEnum::RED));
        self::assertSame(MessageStatus::DONE, $provider->getStatus('red')->status('id'));
    }

    public function testMissingQueueIsDeterministic(): void
    {
        $provider = new QueueProducerStatusProvider(new PredefinedQueueProvider([]));
        self::assertFalse($provider->hasStatus('missing'));
        self::expectException(QueueNotFoundException::class);
        $provider->getStatus('missing');
    }

    public function testSupportsSyncProducerStatus(): void
    {
        $producer = $this->createSyncProducer();
        $provider = new QueueProducerStatusProvider(new PredefinedQueueProvider(['sync' => ['producer' => $producer]]));
        self::assertSame(MessageStatus::NOT_FOUND, $provider->getStatus('sync')->status('id'));
    }

    private function createPushMiddlewareConfig(): PushMiddlewareConfig
    {
        $container = new SimpleContainer();

        return new PushMiddlewareConfig(new PushMiddlewareFactory($container, new CallableFactory($container)));
    }

    private function createSyncProducer(): SyncQueueProducer
    {
        $container = new SimpleContainer();

        return new SyncQueueProducer(new NullLogger(), $this->createPushMiddlewareConfig(), $this->createConcreteWorker());
    }

    private function createConcreteWorker(): Worker
    {
        $container = new SimpleContainer();

        return new Worker(
            new NullLogger(),
            new ConsumeMiddlewareDispatcher(new ConsumeMiddlewareFactory($container, new CallableFactory($container))),
            new FailureMiddlewareDispatcher(new FailureMiddlewareFactory($container, new CallableFactory($container)), []),
            new HandlerResolver([], $container),
        );
    }
}
