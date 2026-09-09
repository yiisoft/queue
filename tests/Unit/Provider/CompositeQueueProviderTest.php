<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Provider\CompositeQueueProvider;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class CompositeQueueProviderTest extends TestCase
{
    public function testCombinesRolesAndPreservesPrecedence(): void
    {
        $firstProducer = $this->createProducer();
        $secondProducer = $this->createProducer();
        $consumer = new QueueConsumer($this->createWorkerFixture(), new SimpleLoop(), new NullLogger());
        $provider = new CompositeQueueProvider(
            new PredefinedQueueProvider(['queue' => ['producer' => $firstProducer]]),
            new PredefinedQueueProvider(['queue' => ['producer' => $secondProducer, 'consumer' => $consumer]]),
        );
        self::assertSame($firstProducer, $provider->getProducer('queue'));
        self::assertSame($consumer, $provider->getConsumer('queue'));
        self::assertSame(['queue'], $provider->getProducerQueueNames());
        self::assertSame(['queue'], $provider->getConsumerQueueNames());
    }

    public function testMissingCapabilityThrows(): void
    {
        $provider = new CompositeQueueProvider(new PredefinedQueueProvider(['queue' => ['producer' => $this->createProducer()]]));
        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer('queue');
    }

    private function createProducer(): SyncQueueProducer
    {
        $container = new SimpleContainer();

        return new SyncQueueProducer(
            new NullLogger(),
            new PushMiddlewareConfig(new PushMiddlewareFactory($container)),
            $this->createWorkerFixture(),
        );
    }

    private function createWorkerFixture(): Worker
    {
        $container = new SimpleContainer();

        return new Worker(
            new NullLogger(),
            new ConsumeMiddlewareDispatcher(new ConsumeMiddlewareFactory($container)),
            new FailureMiddlewareDispatcher(new FailureMiddlewareFactory($container), []),
            new HandlerResolver([], $container),
        );
    }
}
