<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Log\NullLogger;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\QueueProducerStatusInterface;
use Yiisoft\Queue\Tests\TestCase;

final class QueueProducerTest extends TestCase
{
    public function testDefaultStatusDelegatesToAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())
            ->method('status')
            ->with('message-id')
            ->willReturn(MessageStatus::DONE);

        $producer = new AsyncQueueProducer(
            new NullLogger(),
            $this->getPushMiddlewareConfig(),
            $adapter,
        );

        self::assertSame(MessageStatus::DONE, $producer->getStatus()->status('message-id'));
    }

    public function testInjectedStatusIsReturned(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $status = $this->createMock(QueueProducerStatusInterface::class);

        $producer = new AsyncQueueProducer(
            new NullLogger(),
            $this->getPushMiddlewareConfig(),
            $adapter,
            status: $status,
        );

        self::assertSame($status, $producer->getStatus());
    }
}
