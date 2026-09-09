<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueProducerStatusProviderProxy;
use Yiisoft\Queue\Provider\QueueProducerStatusProviderInterface;
use Yiisoft\Queue\QueueProducerStatusInterface;

enum DebugQueue: string
{
    case MAIN = 'main';
}

final class QueueProducerStatusProviderProxyTest extends TestCase
{
    public function testDelegatesCapabilitiesAndWrapsStatusWithoutCallingIt(): void
    {
        $status = $this->createMock(QueueProducerStatusInterface::class);
        $provider = $this->createMock(QueueProducerStatusProviderInterface::class);
        $provider->expects(self::once())->method('getStatus')->with('main')->willReturn($status);
        $provider->expects(self::once())->method('hasStatus')->with('main')->willReturn(true);
        $provider->expects(self::once())->method('getStatusQueueNames')->willReturn(['main']);

        $proxy = new QueueProducerStatusProviderProxy($provider, new QueueCollector());
        self::assertInstanceOf(QueueProducerStatusInterface::class, $proxy->getStatus(DebugQueue::MAIN));
        self::assertTrue($proxy->hasStatus(DebugQueue::MAIN));
        self::assertSame(['main'], $proxy->getStatusQueueNames());
    }
}
