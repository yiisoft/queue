<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Cli\StubLoop;
use Yiisoft\Queue\Provider\ChannelNotFoundException;
use Yiisoft\Queue\Provider\QueueFactoryQueueProvider;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\StubQueue;

final class QueueFactoryQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $provider = new QueueFactoryQueueProvider(
            [
                'channel1' => StubQueue::class,
            ],
        );

        $queue = $provider->get('channel1');

        $this->assertInstanceOf(StubQueue::class, $queue);
        $this->assertSame('channel1', $queue->getChannelName());
        $this->assertTrue($provider->has('channel1'));
        $this->assertFalse($provider->has('not-exist-channel'));
    }

    public function testGetTwice(): void
    {
        $provider = new QueueFactoryQueueProvider(
            [
                'channel1' => StubQueue::class,
            ],
        );

        $queue1 = $provider->get('channel1');
        $queue2 = $provider->get('channel1');

        $this->assertSame($queue1, $queue2);
    }

    public function testGetNotExistChannel()
    {
        $provider = new QueueFactoryQueueProvider(
            [
                'channel1' => StubQueue::class,
            ],
        );

        $this->expectException(ChannelNotFoundException::class);
        $this->expectExceptionMessage('Channel "not-exist-channel" not found.');
        $provider->get('not-exist-channel');
    }

    public function testInvalidQueueConfig(): void
    {
        $definitions = [
            'channel1' => [
                'class' => StubQueue::class,
                '__construct()' => 'hello',
            ],
        ];

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: incorrect constructor arguments. Expected array, got string.'
        );
        new QueueFactoryQueueProvider($definitions);
    }

    public function testInvalidQueueConfigOnGet(): void
    {
        $provider = new QueueFactoryQueueProvider([
            'channel1' => StubLoop::class,
        ]);

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Queue must implement "%s". For channel "channel1" got "%s" instead.',
                QueueInterface::class,
                StubLoop::class,
            )
        );
        $provider->get('channel1');
    }
}
