<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Stubs\StubLoop;
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
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
                'queue1' => StubAdapter::class,
            ],
        );

        /** @var StubQueue $queue */
        $queue = $provider->get('queue1');

        $this->assertInstanceOf(StubQueue::class, $queue);
        $this->assertSame('queue1', $queue->getName());
        $this->assertInstanceOf(StubAdapter::class, $queue->getAdapter());
        $this->assertTrue($provider->has('queue1'));
        $this->assertFalse($provider->has('not-exist-queue'));
    }

    public function testGetTwice(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'queue1' => StubAdapter::class,
            ],
        );

        $queue1 = $provider->get('queue1');
        $queue2 = $provider->get('queue1');

        $this->assertSame($queue1, $queue2);
    }

    public function testGetNotExistQueue(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'queue1' => StubAdapter::class,
            ],
        );

        $this->expectException(QueueNotFoundException::class);
        $this->expectExceptionMessage('Queue with name "not-exist-queue" not found.');
        $provider->get('not-exist-queue');
    }

    public function testInvalidQueueConfig(): void
    {
        $baseQueue = new StubQueue();
        $definitions = [
            'queue1' => [
                'class' => StubAdapter::class,
                '__construct()' => 'hello',
            ],
        ];

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: incorrect constructor arguments. Expected array, got string.',
        );
        new AdapterFactoryQueueProvider($baseQueue, $definitions);
    }

    public function testInvalidQueueConfigOnGet(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'queue1' => StubLoop::class,
            ],
        );

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Adapter must implement "%s". For queue "%s" got "%s" instead.',
                AdapterInterface::class,
                'queue1',
                StubLoop::class,
            ),
        );
        $provider->get('queue1');
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

        $this->assertSame('red', $queue->getName());
        $this->assertTrue($provider->has(StringEnum::RED));
        $this->assertFalse($provider->has(StringEnum::GREEN));
    }

    public function testQueueNameAndAdapterConfiguration(): void
    {
        $provider = new AdapterFactoryQueueProvider(
            new StubQueue(),
            [
                'mail-queue' => [
                    'class' => StubAdapter::class,
                ],
                'log-queue' => StubAdapter::class,
            ],
        );

        /** @var StubQueue<StubAdapter> $mailQueue */
        $mailQueue = $provider->get('mail-queue');
        /** @var StubQueue<StubAdapter> $logQueue */
        $logQueue = $provider->get('log-queue');

        $this->assertSame('mail-queue', $mailQueue->getName());
        $this->assertInstanceOf(StubAdapter::class, $mailQueue->getAdapter());

        $this->assertSame('log-queue', $logQueue->getName());
        $this->assertInstanceOf(StubAdapter::class, $logQueue->getAdapter());
    }
}
