<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\Reference;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\StubAdapter;
use Yiisoft\Queue\Stubs\StubLoop;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function sprintf;

final class QueueFactoryProviderTest extends TestCase
{
    public function testBase(): void
    {
        $provider = new QueueFactoryProvider(
            [
                'queue1' => StubQueue::class,
            ],
        );

        $queue = $provider->get('queue1');

        $this->assertInstanceOf(StubQueue::class, $queue);
        $this->assertTrue($provider->has('queue1'));
        $this->assertFalse($provider->has('not-exist-queue'));
    }

    public function testGetTwice(): void
    {
        $provider = new QueueFactoryProvider(
            [
                'queue1' => StubQueue::class,
            ],
        );

        $queue1 = $provider->get('queue1');
        $queue2 = $provider->get('queue1');

        $this->assertSame($queue1, $queue2);
    }

    public function testGetNotExistQueue(): void
    {
        $provider = new QueueFactoryProvider(
            [
                'queue1' => StubQueue::class,
            ],
        );

        $this->expectException(QueueNotFoundException::class);
        $this->expectExceptionMessage('Queue with name "not-exist-queue" not found.');
        $provider->get('not-exist-queue');
    }

    public function testInvalidQueueConfig(): void
    {
        $definitions = [
            'queue1' => [
                'class' => StubQueue::class,
                '__construct()' => 'hello',
            ],
        ];

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: incorrect constructor arguments. Expected array, got string.',
        );
        new QueueFactoryProvider($definitions);
    }

    public function testInvalidQueueConfigOnGet(): void
    {
        $provider = new QueueFactoryProvider(
            [
                'queue1' => StubLoop::class,
            ],
        );

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Queue must implement "%s". For queue "%s" got "%s" instead.',
                QueueInterface::class,
                'queue1',
                StubLoop::class,
            ),
        );
        $provider->get('queue1');
    }

    public function testGetHasByStringEnum(): void
    {
        $provider = new QueueFactoryProvider(
            [
                'red' => StubQueue::class,
            ],
        );

        $queue = $provider->get(StringEnum::RED);

        $this->assertInstanceOf(StubQueue::class, $queue);
        $this->assertTrue($provider->has(StringEnum::RED));
        $this->assertFalse($provider->has(StringEnum::GREEN));
    }

    public function testWithContainer(): void
    {
        $adapter = new StubAdapter();
        $container = new SimpleContainer([
            AdapterInterface::class => $adapter,
        ]);

        $provider = new QueueFactoryProvider(
            [
                'queue1' => [
                    'class' => StubQueue::class,
                    '__construct()' => [
                        'adapter' => Reference::to(AdapterInterface::class),
                    ],
                ],
            ],
            $container,
        );

        $queue = $provider->get('queue1');

        $this->assertInstanceOf(StubQueue::class, $queue);
        $this->assertSame($adapter, $queue->getAdapter());
    }

    public function testValidateFalse(): void
    {
        $provider = new QueueFactoryProvider(
            [
                'queue1' => [
                    'class' => StubQueue::class,
                    '__construct()' => 'hello',
                ],
            ],
            validate: false,
        );

        $this->assertTrue($provider->has('queue1'));
    }
}
