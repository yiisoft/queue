<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class PredefinedQueueProviderTest extends TestCase
{
    public function testProvidesIndependentRoles(): void
    {
        $producer = $this->createProducer();
        $consumer = new QueueConsumer($this->createWorkerFixture(), new SimpleLoop(), new NullLogger());
        $provider = new PredefinedQueueProvider(['queue1' => ['producer' => $producer, 'consumer' => $consumer]]);
        self::assertSame($producer, $provider->getProducer('queue1'));
        self::assertSame($consumer, $provider->getConsumer('queue1'));
        self::assertSame(['queue1'], $provider->getProducerQueueNames());
        self::assertSame(['queue1'], $provider->getConsumerQueueNames());
    }

    public function testCapabilityIsolationAndEnumNames(): void
    {
        $provider = new PredefinedQueueProvider(['red' => ['producer' => $this->createProducer()]]);
        self::assertTrue($provider->hasProducer(StringEnum::RED));
        self::assertFalse($provider->hasConsumer(StringEnum::RED));
        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer(StringEnum::RED);
    }

    public function testRejectsFlatAndInvalidRoleMaps(): void
    {
        foreach ([['queue' => $this->createProducer()], ['queue' => []], ['queue' => ['unknown' => $this->createProducer()]]] as $queues) {
            try {
                new PredefinedQueueProvider($queues);
                self::fail('Invalid role maps must be rejected.');
            } catch (InvalidQueueConfigException $exception) {
                self::assertStringContainsString('Queue', $exception->getMessage());
            }
        }
    }

    public function testRejectsWrongRoleInstance(): void
    {
        $this->expectException(InvalidQueueConfigException::class);
        new PredefinedQueueProvider(['queue' => ['producer' => new QueueConsumer($this->createWorkerFixture(), new SimpleLoop(), new NullLogger())]]);
    }

    private function createProducer(): SyncQueueProducer
    {
        $container = new SimpleContainer();

        return new SyncQueueProducer(
            new NullLogger(),
            new PushMiddlewareConfig(new PushMiddlewareFactory($container, new CallableFactory($container))),
            $this->createWorkerFixture(),
        );
    }

    private function createWorkerFixture(): Worker
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
