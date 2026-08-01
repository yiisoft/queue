<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Provider\CompositeQueueProvider;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Stubs\StubQueueConsumer;
use Yiisoft\Queue\Stubs\StubQueueProducer;

final class CompositeQueueProviderTest extends TestCase
{
    public function testCombinesRolesAndPreservesPrecedence(): void
    {
        $firstProducer = new StubQueueProducer('first');
        $provider = new CompositeQueueProvider(
            new PredefinedQueueProvider(['queue' => ['producer' => $firstProducer]]),
            new PredefinedQueueProvider(['queue' => ['producer' => new StubQueueProducer('second'), 'consumer' => new StubQueueConsumer()]]),
        );
        self::assertSame($firstProducer, $provider->getProducer('queue'));
        self::assertInstanceOf(StubQueueConsumer::class, $provider->getConsumer('queue'));
        self::assertSame(['queue'], $provider->getProducerNames());
        self::assertSame(['queue'], $provider->getConsumerNames());
    }

    public function testMissingCapabilityThrows(): void
    {
        $provider = new CompositeQueueProvider(new PredefinedQueueProvider(['queue' => ['producer' => new StubQueueProducer()]]));
        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer('queue');
    }
}
