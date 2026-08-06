<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Stubs\StubLoop;
use Yiisoft\Queue\Stubs\StubQueueConsumer;
use Yiisoft\Queue\Stubs\StubQueueProducer;

final class QueueFactoryProviderTest extends TestCase
{
    public function testLazilyCreatesRolesIndependently(): void
    {
        $provider = new QueueFactoryProvider(['queue' => ['producer' => StubQueueProducer::class, 'consumer' => StubQueueConsumer::class]]);
        self::assertInstanceOf(StubQueueProducer::class, $provider->getProducer('queue'));
        self::assertSame($provider->getProducer('queue'), $provider->getProducer('queue'));
        self::assertInstanceOf(StubQueueConsumer::class, $provider->getConsumer('queue'));
        self::assertSame(['queue'], $provider->getProducerNames());
        self::assertSame(['queue'], $provider->getConsumerNames());
    }

    public function testCapabilityIsolation(): void
    {
        $provider = new QueueFactoryProvider(['producer-only' => ['producer' => StubQueueProducer::class]]);
        self::assertTrue($provider->hasProducer('producer-only'));
        self::assertFalse($provider->hasConsumer('producer-only'));
        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer('producer-only');
    }

    public function testRejectsFlatEmptyAndUnknownRoleMaps(): void
    {
        foreach ([['queue' => StubQueueProducer::class], ['queue' => []], ['queue' => ['unknown' => StubQueueProducer::class]]] as $definitions) {
            try {
                new QueueFactoryProvider($definitions);
                self::fail('Invalid role maps must be rejected.');
            } catch (InvalidQueueConfigException) {
                self::addToAssertionCount(1);
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
            } catch (InvalidQueueConfigException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
