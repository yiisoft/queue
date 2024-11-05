<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use Yiisoft\Queue\Provider\ChannelNotFoundException;
use Yiisoft\Queue\Provider\CompositeQueueProvider;
use Yiisoft\Queue\Provider\FactoryQueueProvider;
use Yiisoft\Queue\StubQueue;
use Yiisoft\Queue\Tests\TestCase;

final class CompositeQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $provider = new CompositeQueueProvider(
            new FactoryQueueProvider([
                'channel1' => new StubQueue('channel1'),
            ]),
            new FactoryQueueProvider([
                'channel2' => new StubQueue('channel2'),
            ]),
        );

        $this->assertTrue($provider->has('channel1'));
        $this->assertTrue($provider->has('channel2'));
        $this->assertFalse($provider->has('channel3'));

        $this->assertSame('channel1', $provider->get('channel1')->getChannelName());
        $this->assertSame('channel2', $provider->get('channel2')->getChannelName());
    }

    public function testNotFound(): void
    {
        $provider = new CompositeQueueProvider(
            new FactoryQueueProvider([
                'channel1' => new StubQueue(),
            ]),
        );

        $this->expectException(ChannelNotFoundException::class);
        $this->expectExceptionMessage('Channel "not-exist" not found.');
        $provider->get('not-exist');
    }
}
