<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Stubs\StubQueueConsumer;
use Yiisoft\Queue\Stubs\StubQueueProducer;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

final class PredefinedQueueProviderTest extends TestCase
{
    public function testProvidesIndependentRoles(): void
    {
        $producer = new StubQueueProducer();
        $consumer = new StubQueueConsumer();
        $provider = new PredefinedQueueProvider(['queue1' => ['producer' => $producer, 'consumer' => $consumer]]);

        self::assertSame($producer, $provider->getProducer('queue1'));
        self::assertSame($consumer, $provider->getConsumer('queue1'));
        self::assertSame(['queue1'], $provider->getProducerNames());
        self::assertSame(['queue1'], $provider->getConsumerNames());
    }

    public function testCapabilityIsolationAndEnumNames(): void
    {
        $provider = new PredefinedQueueProvider(['red' => ['producer' => new StubQueueProducer()]]);
        self::assertTrue($provider->hasProducer(StringEnum::RED));
        self::assertFalse($provider->hasConsumer(StringEnum::RED));
        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer(StringEnum::RED);
    }

    public function testRejectsFlatAndInvalidRoleMaps(): void
    {
        foreach ([['queue' => new StubQueueProducer()], ['queue' => []], ['queue' => ['unknown' => new StubQueueProducer()]]] as $queues) {
            try {
                new PredefinedQueueProvider($queues);
                self::fail('Invalid role maps must be rejected.');
            } catch (InvalidQueueConfigException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testRejectsWrongRoleInstance(): void
    {
        $this->expectException(InvalidQueueConfigException::class);
        new PredefinedQueueProvider(['queue' => ['producer' => new StubQueueConsumer()]]);
    }
}
