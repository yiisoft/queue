<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yiisoft\Queue\Cli\StubLoop;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPushInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Provider\PrototypeQueueProvider;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Worker\StubWorker;

final class PrototypeQueueProviderTest extends TestCase
{
    public function testBase(): void
    {
        $provider = new PrototypeQueueProvider(
            new Queue(
                new StubWorker(),
                new StubLoop(),
                new NullLogger(),
                new PushMiddlewareDispatcher(
                    $this->createMock(MiddlewareFactoryPushInterface::class)
                ),
            ),
        );

        $queue = $provider->get('test-channel');

        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertSame('test-channel', $queue->getChannelName());
        $this->assertTrue($provider->has('test-channel'));
        $this->assertTrue($provider->has('yet-another-channel'));
    }
}
