<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\Stubs\StubLoop;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Psr\Log\NullLogger;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class QueueFactoryProviderTest extends TestCase
{
    public function testLazilyCreatesRolesIndependently(): void
    {
        $producer = $this->createProducer();
        $consumer = new QueueConsumer($this->createWorkerFixture(), new SimpleLoop(), new NullLogger());
        $provider = new QueueFactoryProvider(['queue' => ['producer' => $producer, 'consumer' => $consumer]]);
        $resolvedProducer = $provider->getProducer('queue');
        self::assertInstanceOf(SyncQueueProducer::class, $resolvedProducer);
        self::assertSame($producer->getQueueName(), $resolvedProducer->getQueueName());
        self::assertSame($resolvedProducer, $provider->getProducer('queue'));
        $resolvedConsumer = $provider->getConsumer('queue');
        self::assertInstanceOf(QueueConsumer::class, $resolvedConsumer);
        self::assertSame(0, $resolvedConsumer->run());
        self::assertSame($resolvedConsumer, $provider->getConsumer('queue'));
        self::assertSame(['queue'], $provider->getProducerQueueNames());
        self::assertSame(['queue'], $provider->getConsumerQueueNames());
    }

    public function testCapabilityIsolation(): void
    {
        $provider = new QueueFactoryProvider(['producer-only' => ['producer' => $this->createProducer()]]);
        self::assertTrue($provider->hasProducer('producer-only'));
        self::assertFalse($provider->hasConsumer('producer-only'));
        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer('producer-only');
    }

    public function testRejectsFlatEmptyAndUnknownRoleMaps(): void
    {
        foreach ([['queue' => $this->createProducer()], ['queue' => []], ['queue' => ['unknown' => $this->createProducer()]]] as $definitions) {
            try {
                new QueueFactoryProvider($definitions);
                self::fail('Invalid role maps must be rejected.');
            } catch (InvalidQueueConfigException $exception) {
                self::assertStringContainsString('Queue', $exception->getMessage());
            }
        }
    }

    public function testRejectsWrongRoleOnResolutionAndCachesFailure(): void
    {
        $provider = new QueueFactoryProvider(['queue' => ['producer' => StubLoop::class]]);
        foreach ([1, 2] as $_) {
            try {
                $provider->getProducer('queue');
                self::fail('Wrong role must be rejected.');
            } catch (InvalidQueueConfigException $exception) {
                self::assertStringContainsString('Queue', $exception->getMessage());
            }
        }
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
