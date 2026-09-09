<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Log\NullLogger;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\QueueProducerStatusInterface;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Worker\Worker;

final class SyncQueueProducerTest extends TestCase
{
    public function testDefaultStatusIsNotFound(): void
    {
        $producer = new SyncQueueProducer(
            new NullLogger(),
            $this->getPushMiddlewareConfig(),
            $this->createConcreteWorker(),
        );

        self::assertSame(MessageStatus::NOT_FOUND, $producer->getStatus()->status('message-id'));
    }

    public function testInjectedStatusIsReturned(): void
    {
        $status = $this->createMock(QueueProducerStatusInterface::class);

        $producer = new SyncQueueProducer(
            new NullLogger(),
            $this->getPushMiddlewareConfig(),
            $this->createConcreteWorker(),
            status: $status,
        );

        self::assertSame($status, $producer->getStatus());
    }

    private function createConcreteWorker(): Worker
    {
        return new Worker(
            new NullLogger(),
            $this->getConsumeMiddlewareDispatcher(),
            $this->getFailureMiddlewareDispatcher(),
            new HandlerResolver($this->getMessageHandlers(), $this->getContainer()),
        );
    }
}
