<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Stubs\StubLoop;
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Provider\ChannelNotFoundException;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Stubs\StubAdapter;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

use function sprintf;

final class AdapterFactoryQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'channel1' => StubAdapter::class,
            ],
        );

        $queue = $provider->get('channel1');

        $this->assertInstanceOf(StubQueue::class, $queue);
        $this->assertSame('channel1', $queue->getChannel());
        $this->assertInstanceOf(StubAdapter::class, $queue->getAdapter());
        $this->assertTrue($provider->has('channel1'));
        $this->assertFalse($provider->has('not-exist-channel'));
    }

    public function testGetTwice(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'channel1' => StubAdapter::class,
            ],
        );

        $queue1 = $provider->get('channel1');
        $queue2 = $provider->get('channel1');

        $this->assertSame($queue1, $queue2);
    }

    public function testGetNotExistChannel(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'channel1' => StubAdapter::class,
            ],
        );

        $this->expectException(ChannelNotFoundException::class);
        $this->expectExceptionMessage('Channel "not-exist-channel" not found.');
        $provider->get('not-exist-channel');
    }

    public function testInvalidQueueConfig(): void
    {
        $baseQueue = new StubQueue();
        $definitions = [
            'channel1' => [
                'class' => StubAdapter::class,
                '__construct()' => 'hello',
            ],
        ];

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: incorrect constructor arguments. Expected array, got string.'
        );
        new AdapterFactoryQueueProvider($baseQueue, $definitions);
    }

    public function testInvalidQueueConfigOnGet(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'channel1' => StubLoop::class,
            ]
        );

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Adapter must implement "%s". For channel "channel1" got "%s" instead.',
                AdapterInterface::class,
                StubLoop::class,
            )
        );
        $provider->get('channel1');
    }

    public function testGetHasByStringEnum(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'red' => StubAdapter::class,
            ],
        );

        $queue = $provider->get(StringEnum::RED);

        $this->assertSame('red', $queue->getChannel());
        $this->assertTrue($provider->has(StringEnum::RED));
        $this->assertFalse($provider->has(StringEnum::GREEN));
    }
}
