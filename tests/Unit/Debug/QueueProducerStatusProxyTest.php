<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueProducerStatusProxy;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\QueueProducerStatusInterface;

final class QueueProducerStatusProxyTest extends TestCase
{
    public function testDelegatesLazilyAndCollectsStatus(): void
    {
        $status = $this->createMock(QueueProducerStatusInterface::class);
        $status->expects(self::once())->method('status')->with('id')->willReturn(MessageStatus::DONE);
        $collector = new QueueCollector();
        $collector->startup();
        $proxy = new QueueProducerStatusProxy($status, $collector);

        self::assertSame(MessageStatus::DONE, $proxy->status('id'));
        self::assertSame('id', $collector->getCollected()['statuses'][0]['id']);
        self::assertSame('done', $collector->getCollected()['statuses'][0]['status']);
    }

    public function testInactiveCollectorStillDelegates(): void
    {
        $status = $this->createMock(QueueProducerStatusInterface::class);
        $status->expects(self::once())->method('status')->willReturn(MessageStatus::WAITING);
        $collector = new QueueCollector();
        self::assertSame(MessageStatus::WAITING, (new QueueProducerStatusProxy($status, $collector))->status(1));
        self::assertSame([], $collector->getCollected());
    }
}
