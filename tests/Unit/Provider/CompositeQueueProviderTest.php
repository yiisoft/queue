<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Provider\CompositeQueueProvider;
use Yiisoft\Queue\Stubs\StubAdapter;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Tests\TestCase;

final class CompositeQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $queue1 = new StubQueue(new StubAdapter());
        $queue2 = new StubQueue(new StubAdapter());
        $provider = new CompositeQueueProvider(
            new PredefinedQueueProvider(['queue1' => $queue1]),
            new PredefinedQueueProvider(['queue2' => $queue2]),
        );

        $this->assertTrue($provider->has('queue1'));
        $this->assertTrue($provider->has('queue2'));
        $this->assertFalse($provider->has('queue3'));

        $this->assertSame($queue1, $provider->get('queue1'));
        $this->assertSame($queue2, $provider->get('queue2'));
    }

    public function testNotFound(): void
    {
        $provider = new CompositeQueueProvider(
            new AdapterFactoryQueueProvider(
                new StubQueue(new StubAdapter()),
                ['channel1' => new StubAdapter()],
            ),
        );

        $this->expectException(QueueNotFoundException::class);
        $this->expectExceptionMessage('Queue with name "not-exists" not found.');
        $provider->get('not-exists');
    }
}
