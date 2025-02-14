<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Provider\PrototypeQueueProvider;
use Yiisoft\Queue\Stubs\StubAdapter;
use Yiisoft\Queue\Stubs\StubQueue;

final class PrototypeQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $provider = new PrototypeQueueProvider(
            new StubQueue(),
            new StubAdapter(),
        );

        $queue = $provider->get('test-channel');

        $this->assertInstanceOf(StubQueue::class, $queue);
        $this->assertSame('test-channel', $queue->getChannel());
        $this->assertTrue($provider->has('test-channel'));
        $this->assertTrue($provider->has('yet-another-channel'));
    }
}
