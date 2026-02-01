<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Provider\ChannelNotFoundException;
use Yiisoft\Queue\Provider\CompositeQueueProvider;
use Yiisoft\Queue\Stubs\StubAdapter;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Tests\TestCase;

final class CompositeQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $queue = new StubQueue(new StubAdapter());
        $provider = new CompositeQueueProvider(
            new AdapterFactoryQueueProvider(
                $queue,
                ['channel1' => new StubAdapter()],
            ),
            new AdapterFactoryQueueProvider(
                $queue,
                ['channel2' => new StubAdapter()],
            ),
        );

        $this->assertTrue($provider->has('channel1'));
        $this->assertTrue($provider->has('channel2'));
        $this->assertFalse($provider->has('channel3'));

        $this->assertSame('channel1', $provider->get('channel1')->getChannel());
        $this->assertSame('channel2', $provider->get('channel2')->getChannel());
    }

    public function testNotFound(): void
    {
        $provider = new CompositeQueueProvider(
            new AdapterFactoryQueueProvider(
                new StubQueue(new StubAdapter()),
                ['channel1' => new StubAdapter()],
            ),
        );

        $this->expectException(ChannelNotFoundException::class);
        $this->expectExceptionMessage('Channel "not-exists" not found.');
        $provider->get('not-exists');
    }
}
