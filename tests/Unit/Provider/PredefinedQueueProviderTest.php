<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

use stdClass;

use function sprintf;

final class PredefinedQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $queue = new StubQueue();
        $provider = new PredefinedQueueProvider([
            'queue1' => $queue,
        ]);

        $this->assertSame($queue, $provider->get('queue1'));
        $this->assertTrue($provider->has('queue1'));
        $this->assertFalse($provider->has('not-exist-queue'));
    }

    public function testGetTwice(): void
    {
        $queue = new StubQueue();
        $provider = new PredefinedQueueProvider([
            'queue1' => $queue,
        ]);

        $queue1 = $provider->get('queue1');
        $queue2 = $provider->get('queue1');

        $this->assertSame($queue1, $queue2);
    }

    public function testGetNotExistQueue(): void
    {
        $provider = new PredefinedQueueProvider([
            'queue1' => new StubQueue(),
        ]);

        $this->expectException(QueueNotFoundException::class);
        $this->expectExceptionMessage('Queue with name "not-exist-queue" not found.');
        $provider->get('not-exist-queue');
    }

    public function testInvalidQueueConfig(): void
    {
        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Queue must implement "%s". For queue "%s" got "%s" instead.',
                QueueInterface::class,
                'queue1',
                'stdClass',
            ),
        );
        new PredefinedQueueProvider([
            'queue1' => new stdClass(),
        ]);
    }

    public function testGetHasByStringEnum(): void
    {
        $queue = new StubQueue();
        $provider = new PredefinedQueueProvider([
            'red' => $queue,
        ]);

        $this->assertSame($queue, $provider->get(StringEnum::RED));
        $this->assertTrue($provider->has(StringEnum::RED));
        $this->assertFalse($provider->has(StringEnum::GREEN));
    }

    public function testEmpty(): void
    {
        $provider = new PredefinedQueueProvider([]);

        $this->assertFalse($provider->has('any'));
    }

    public function testGetNames(): void
    {
        $provider = new PredefinedQueueProvider([
            'queue1' => new StubQueue(),
            'queue2' => new StubQueue(),
        ]);

        $this->assertSame(['queue1', 'queue2'], $provider->getNames());
    }

    public function testGetNamesEmpty(): void
    {
        $provider = new PredefinedQueueProvider([]);

        $this->assertSame([], $provider->getNames());
    }
}
